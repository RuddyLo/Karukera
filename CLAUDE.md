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

**Constantes dans `StripeController.php` :**
- `STRIPE_FEE_RATE = 0.015` (1.5%)
- `STRIPE_FEE_FIXED = 0.25` (€0.25 fixe)
- `STRIPE_FX_RATE = 0.02` (2% frais conversion devise Stripe — ⚠️ susceptible d'évoluer)
- Les frais Stripe sont appliqués sur la caution uniquement et payés par le client

⚠️ **Montant de la caution : PAS 30% du loyer.** `createPaymentIntent` calcule un montant **fixe** selon la durée du séjour : `$caution = $days <= 3 ? 400.0 : 500.0;`. La constante `CAUTION_RATE = 0.30` existe dans le code mais n'est utilisée nulle part (constante morte) — corrigé dans la doc le 2026-07-22, ne pas se fier à l'ancienne mention "30% du loyer".

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

## Calendrier de réservation — turnover le jour du checkout

Le jour de checkout d'une réservation redevient disponible en check-in pour le client suivant (rotation le même jour, ex: résa A 01→03 juillet, résa B peut commencer le 03).

- `ApartmentController::reservationsJson` : envoie `endDate` tel quel à FullCalendar (pas de `+1 day`), FullCalendar traite `end` comme exclusif nativement
- `details.html.twig` : `hasReservedInRange()` ne vérifie plus le jour de checkout de la sélection candidate (sinon un enchaînement à 3 réservations ou plus casserait sur la 2e transition)
- Corrigé le 2026-07-22 — avant ce fix, le jour de checkout apparaissait à tort bloqué pour tout le monde

## Description appartement — troncature + modal

`templates/apartments/details.html.twig` : la description est tronquée à 100 mots (`split(' ')|slice(0,100)|join(' ')`), avec un bouton "Lire la suite" (fond `var(--color-primary)`, texte blanc) qui ouvre une modale Bootstrap (`#description-modal`, `modal-xl`) affichant le texte complet.
⚠️ `.modal-content` a un style global semi-transparent + flou (`main.css:138`) — cette modale override en `background:#fff; backdrop-filter:none` pour rester lisible. Toute nouvelle modale avec du texte dense devrait faire pareil.
Clés de traduction : `apartment_details.read_more`, `apartment_details.description_title` (FR/EN).
