# Karukera — CLAUDE.md

Plateforme de location saisonnière (Guadeloupe). Symfony 7 + Docker + Stripe.

## Stack technique

- **Backend** : Symfony 7, PHP, Doctrine ORM, EasyAdmin
- **Frontend** : Twig, Webpack Encore, Stimulus, Stripe.js
- **BDD** : MySQL 8 (`karukera`)
- **Paiement** : Stripe (Payment Elements, webhooks)
- **Mail** : SMTP OVH via Symfony Mailer
- **Captcha** : reCAPTCHA v3 (karser/karser-recaptcha3-bundle) — présent sur tous les formulaires
- **Docker** : app sur `:8001`, phpMyAdmin sur `:8080`

## Structure du projet

```
/plateforme
├── project/              ← racine Symfony
│   ├── src/
│   │   ├── Controller/   ← logique HTTP
│   │   ├── Entity/       ← Doctrine entities
│   │   └── ...
│   ├── templates/        ← vues Twig
│   ├── assets/js/        ← JS frontend (reservation.js, etc.)
│   └── migrations/
├── docker/
├── docker-compose.yml
└── Makefile
```

## Entités principales

- `Apartment` — appartements avec prix, équipements, images
- `Reservation` — réservation avec `rentPaymentIntentId` + `cautionPaymentIntentId`
- `Payment` — enregistre chaque PaymentIntent (montant, devise, statut)
- `PricePeriod` — périodes tarifaires par appartement
- `MinimumStayPeriod` — séjour minimum par période
- `User` — locataires + admins
- `Review` — avis clients

## Workflow Stripe (2 paiements séparés)

1. Client choisit des dates → modal récap s'ouvre
2. **PaymentIntent 1** : loyer → capturé immédiatement
3. Redirection vers `stripe/processing.html.twig`
4. **PaymentIntent 2** : caution + frais Stripe → capturé immédiatement
5. Webhook `payment_intent.succeeded` → crée la `Reservation` en BDD
6. Redirection `/payment/success`

**Fallback si le webhook ne se déclenche pas** : la route `/payment/success` (`StripeController::success`) re-vérifie elle-même le statut du PaymentIntent auprès de Stripe et crée la `Reservation` si elle n'existe pas encore — même logique de `findOneBy(['user','apartment','startDate'])` que le webhook pour éviter les doublons. Donc même si le webhook est en retard/absent, la page success crée la réservation à la place.

⚠️ **Piège test → live** : `UserReservationsController::index` (page "Mes réservations") appelle `Stripe::setApiKey(...)` **hors du try/catch** qui protège les `PaymentIntent::retrieve()` juste après — une clé mal formée (espace, retour à la ligne, guillemets collés par erreur) y plante en 500 direct, sans être rattrapée. Et les anciennes réservations créées avec des PaymentIntent **test** ne seront plus jamais récupérables via une clé **live** (Stripe sépare les deux univers) — déjà géré proprement par le try/catch existant (affiche juste "paiement indisponible"), ce n'est pas un bug. Un webhook live nécessite un **nouvel endpoint** créé dans le Dashboard Stripe (mode Live) → nouveau `whsec_...`, celui de test ne fonctionne pas.

**Constantes dans `StripeController.php` :**
- `STRIPE_FEE_RATE = 0.015` (1.5%)
- `STRIPE_FEE_FIXED = 0.25` (€0.25 fixe)
- `STRIPE_FX_RATE = 0.02` (2% frais conversion devise Stripe — ⚠️ susceptible d'évoluer)
- Les frais Stripe sont appliqués sur la caution uniquement et payés par le client

⚠️ **Caution mise à 0€ (2026-08-23).** `createPaymentIntent` fixe désormais `$caution = 0.0` et `$cautionWithFees = 0.0` (plus de calcul 400€/500€ selon la durée, plus de frais Stripe appliqués dessus). Les constantes `CAUTION_RATE = 0.30` (déjà morte avant ce changement), `STRIPE_FEE_RATE` et `STRIPE_FEE_FIXED` (devenues mortes suite à ce changement) sont laissées en place dans le code mais ne sont plus utilisées nulle part. CGV (`_cgv_karukera.html.twig`, `_cgv_rio.html.twig`, Article 10) mises à jour en conséquence ("Un dépôt de garantie de 0 € est exigé", reste de l'article inchangé — retenues en cas de dégâts, délai de remboursement 7j). Les fichiers `.docx` sources ("contrat location oasis de Karukera.docx", "Contrat de location Oasis do Rio.docx", à la racine du repo) mentionnent encore 400€/500€ — non mis à jour à la demande explicite de l'utilisateur (considérés comme archive, pas la source affichée aux clients). Templates admin/user, JS, emails et traductions n'avaient aucun montant en dur : ils affichent maintenant 0€ automatiquement, sans modification nécessaire.

⚠️ **Piège évité** : `AdminReservationController::refundCaution` appelait `Refund::create(['amount' => 0])` pour une caution à 0€ — Stripe refuse un montant à 0 (erreur API, rattrapée par le try/catch existant mais visible en flash "Erreur Stripe"). Corrigé : l'appel à `Refund::create` est maintenant sauté (`if ((float) $cautionAmount > 0)`) et la réservation est directement marquée `setCautionRefunded(true)` sans transaction Stripe.

⚠️ **Garde-fou dates** : `createPaymentIntent` et `createAdminReservation` rejettent (400) toute requête où `endDate <= startDate` (empêche une "réservation" 0 nuit facturée au prix d'1 nuit). Même garde côté front dans `details.html.twig` (`dateClick` + préremplissage URL).

**Admin bypass** : les admins peuvent créer des réservations sans paiement via `/stripe/create-admin-reservation`.

## Fichiers clés paiement

| Fichier | Rôle |
|---|---|
| `src/Controller/StripeController.php` | Création PaymentIntents, webhook, success |
| `assets/js/reservation.js` | Frontend : formulaire paiement, confirmation Stripe |
| `templates/apartments/details.html.twig` | Modal réservation avec Payment Element |
| `templates/stripe/processing.html.twig` | Page intermédiaire 2e paiement (caution) |

## Support multi-devises EUR / BRL — IMPLÉMENTÉ

**Taux de change** : API Frankfurter (`https://api.frankfurter.app/latest`) — gratuite, sans clé, basée BCE, cache 1h Symfony.

**Frais de conversion Stripe** : +2% appliqué au taux (`STRIPE_FX_RATE = 0.02` dans `StripeController.php`).
⚠️ **Ce taux peut évoluer** — à externaliser en `.env` si Stripe change ses conditions.

**Architecture :**
- `StripeController` : `HttpClientInterface` + `CacheInterface` injectés en constructeur
- `createPaymentIntent` : lit `currency` dans le POST, appelle `getExchangeRate()` si BRL, convertit tous les montants
- Devise + taux de change stockés dans `metadata` du PaymentIntent (`currency`, `exchange_rate`)
- `currency_symbol` ('€' ou 'R$') déduit de `metadata->currency ?? 'eur'` dans tous les controllers

**UX :**
- Sélecteur EUR/BRL dans le **modal** de réservation (aspect-ratio 2/3, style actif/hover CSS)
- Spinner pendant le fetch du taux, taux affiché avec mention `(inclus un frais de conversion de 2%)`
- Changement de devise → re-fetch PaymentIntent + rechargement Payment Element Stripe

**Propagation devise :**
- Page "Mes réservations", admin liste/détail : `payment.currency_symbol` partout
- Refund caution admin : montant + flash message en bonne devise
- Le refund Stripe lui-même est correct nativement (PI en BRL → remboursement en BRL)

## Traductions (i18n FR/EN)

**Page détails appartement** (`templates/apartments/details.html.twig`) :
- Clés traduits : `apartment_details.equipments_title`, `book_title`, `price_per_night`, `show_all_images`, `login_to_book`
- `window.appLocale` injecté par Twig → utilisé par FullCalendar et Stripe Payment Element
- `_locale` ajouté dans tous les `redirectToRoute('app.apartment.details', ...)` (StripeController + ApartmentController)

⚠️ **Traductions incomplètes — page "Mes réservations"** :
- Les modales CGV dans `templates/user/reservations.html.twig` sont en français hardcodé (articles 1–8 + politique confidentialité)
- Reporté volontairement — à traiter dans une session dédiée avec relecture juridique

## Variables d'environnement importantes

```
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
MAILER_DSN=smtp://...@ssl0.ovh.net:465
RECAPTCHA3_KEY=...
RECAPTCHA3_SECRET=...
DATABASE_URL=mysql://root:@mysql_karukera:3306/karukera
```

⚠️ **`project/.env` est suivi par git** (seul `.env.local` est ignoré) — n'y mettre que des placeholders, jamais de vrais secrets. Un secret leak (Stripe, SMTP OVH, reCAPTCHA) a été nettoyé le 2026-07-22 (commit `031d6eb`), historique git conservé tel quel (repo privé).

## Lancer le projet

```bash
docker compose up -d          # démarrer les conteneurs (sans tiret)
# App : http://localhost:8001
# phpMyAdmin : http://localhost:8080
```

## Gestion caution (backoffice admin)

Route : `AdminReservationController::refundCaution` (`/admin/reservation/{id}/refund-caution`). Après le séjour, l'admin peut depuis le backoffice :
- **Rembourser intégralement** : client reçoit le montant caution (sans les frais Stripe) — fonctionnel
- **Conserver la caution** : en cas de gros dégâts — fonctionnel

⚠️ **Remboursement partiel NON implémenté**, malgré l'UI qui le suggère : `templates/admin/reservations/show.html.twig` a un champ `amount` dans `#partial-amount-container`, mais il reste en `display:none` (rien ne le révèle, aucun script ne le montre) et `refundCaution()` ne lit jamais ce paramètre — si "conserver" n'est pas coché, le remboursement est **toujours intégral**. À finir si le besoin de remboursement partiel est confirmé (champ front à révéler + lecture `amount` côté controller + `Refund::create(['amount' => ...])` avec le montant partiel au lieu du montant plein).

### Évolution prévue (non implémentée) : caution en J-2, débit + remboursement manuel

Idée validée avec l'utilisateur le 2026-08-14, à implémenter dans une session dédiée ultérieure.

- Le client recevrait un mail **2 jours avant sa date d'arrivée** l'invitant à payer la caution, au lieu du prélèvement actuel fait au moment de la réservation (fusionné avec le loyer dans le même PaymentIntent, cf. `createPaymentIntent`, `StripeController.php:252-271`).
- Ce n'est **pas** une vraie empreinte bancaire (pré-autorisation `capture_method: manual`) : Stripe annule automatiquement toute autorisation non capturée au bout de ~7 jours, trop court pour couvrir des séjours plus longs. À la place : **capture automatique classique** (un vrai débit) déclenchée à J-2, puis remboursement ou conservation **manuel** en fin de séjour via le mécanisme existant (`AdminReservationController::refundCaution`, section ci-dessus) — pas de changement nécessaire sur cette partie.
- Implique de **désolidariser le PaymentIntent caution du PaymentIntent loyer** : seule la part loyer serait prélevée à la réservation, la caution étant créée et prélevée séparément à J-2.
- Nécessite une **tâche planifiée** (Symfony Command + cron) qui identifie chaque jour les réservations à J+2 et déclenche l'envoi du mail avec un lien de paiement dédié à la caution.
- Cas limite à gérer : réservation faite à **moins de 2 jours** de l'arrivée → pas de J-2 possible, prélever la caution immédiatement comme aujourd'hui.
- Paiement caution pensé "on-session" (client présent, saisit sa carte / passe le 3DS) pour éviter les complications SCA d'un prélèvement off-session automatique sur carte enregistrée.
- **Question ouverte non tranchée** : que faire si le client ne paie pas après le mail (carte refusée, lien ignoré) — bloquer le check-in ? relancer ? annuler la réservation ?

## Calendrier de réservation — turnover le jour du checkout

Le jour de checkout d'une réservation redevient disponible en check-in pour le client suivant (rotation le même jour, ex: résa A 01→03 juillet, résa B peut commencer le 03).

- `ApartmentController::reservationsJson` : envoie `endDate` tel quel à FullCalendar (pas de `+1 day`), FullCalendar traite `end` comme exclusif nativement
- `details.html.twig` : `hasReservedInRange()` ne vérifie plus le jour de checkout de la sélection candidate (sinon un enchaînement à 3 réservations ou plus casserait sur la 2e transition)
- Corrigé le 2026-07-22 — avant ce fix, le jour de checkout apparaissait à tort bloqué pour tout le monde

## Logo & navbar

Logo actuel : `project/public/images/logo-crop.png` (référencé dans `templates/components/navbar.html.twig`), lockup "OK" (icône ronde) + "LES OASIS DE KARURIO" en navy/teal.

- `.navbar-container` (desktop ≥991px, `main.css`) : fond passé de navy translucide (`rgba(var(--color-secondary-rgb), 0.9)`) à blanc uni (`var(--color-white)`) + ombre légère — le texte navy du logo était illisible sur l'ancien fond navy.
- `.navbar a` : couleur passée de blanc à `var(--color-secondary-dark)` (`#3a4753`) pour rester lisible sur le nouveau fond blanc (le blanc était déjà invisible aussi en mode "sticked", bug préexistant corrigé au passage).
- ⚠️ Cette section CSS n'existe que dans la media query `@media (min-width: 991px)` — la navbar mobile (`.mobile-nav-active .navbar`) garde son propre fond navy (menu plein écran séparé), non concerné par ce changement.
- Corrigé le 2026-07-25.

**Hauteur réduite (desktop uniquement, 2026-08-03) :**
- `.navbar-container` : `padding: 5px; padding-left/right: 10px` → `padding: 0 10px` (suppression du padding vertical)
- `.header nav` : padding vertical `15px 0` → `8px 0`, override ajouté dans `@media (min-width: 991px)` (la règle de base hors media query reste à `15px 0` pour le mobile, non concerné par cette demande)
- `.header .logo img` : hauteur `100px` → `70px`, même override desktop-only (le logo mobile garde ses `60px` définis dans `@media (max-width: 990px)`)

## Page détails appartement — responsive calendrier

`templates/apartments/details.html.twig` :
- FullCalendar (`#calendar`) n'avait pas de `headerToolbar` explicite → affichait la toolbar par défaut (prev/next + titre + boutons Mois/Semaine/Jour), inutile ici (calendrier lecture seule pour choisir des dates) et trop large sur mobile → calendrier écrasé. Simplifié en `{ left: 'prev,next', center: 'title', right: '' }`.
- 3 niveaux de padding imbriqués autour du calendrier (`row p-4` → `bg-white p-3` → `border p-4`) restaient identiques sur mobile, cumulant ~176px de padding horizontal sur un écran de 375px. Passés en `p-2 p-md-4` / `p-2 p-md-3` (Bootstrap responsive) pour ne réduire qu'en dessous de `md`, desktop inchangé.
- Corrigé le 2026-07-25.

## Description appartement — troncature + modal

`templates/apartments/details.html.twig` : la description est tronquée à 100 mots (`split(' ')|slice(0,100)|join(' ')`), avec un bouton "Lire la suite" (fond `var(--color-primary)`, texte blanc) qui ouvre une modale Bootstrap (`#description-modal`, `modal-xl`) affichant le texte complet.
⚠️ `.modal-content` a un style global semi-transparent + flou (`main.css:138`) — cette modale override en `background:#fff; backdrop-filter:none` pour rester lisible. Toute nouvelle modale avec du texte dense devrait faire pareil.
Clés de traduction : `apartment_details.read_more`, `apartment_details.description_title` (FR/EN).

## Hero — accroche & badges de confiance

`templates/components/hero.html.twig` : titre changé de "Louez votre appartement au meilleur prix !" → "Réservez en direct, simplement" (EN : "Book direct, made simple"), avec une ligne `.hero-badges` juste dessous : ✓ Tarif avantageux · 🔒 Paiement sécurisé · 💬 Contact direct.

- Nouvelles clés i18n (`messages.fr.yaml` / `messages.en.yaml`) : `hero.title`, `hero.badge_price`, `hero.badge_secure`, `hero.badge_contact`
- CSS `.hero-badges` (`main.css`) : flex centré, même animation `fadeInUp` que le `h1` avec un léger délai (`animation-delay: 0.15s`), variante mobile (`@media max-width: 600px`) avec gap et font-size réduits
- Corrigé le 2026-08-03.
