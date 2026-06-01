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
- `CAUTION_RATE = 0.30` (caution = 30% du loyer)
- `STRIPE_FEE_RATE = 0.015` (1.5%)
- `STRIPE_FEE_FIXED = 0.25` (€0.25 fixe)
- Les frais Stripe sont appliqués sur la caution uniquement et payés par le client

**Admin bypass** : les admins peuvent créer des réservations sans paiement via `/stripe/create-admin-reservation`.

## Fichiers clés paiement

| Fichier | Rôle |
|---|---|
| `src/Controller/StripeController.php` | Création PaymentIntents, webhook, success |
| `assets/js/reservation.js` | Frontend : formulaire paiement, confirmation Stripe |
| `templates/apartments/details.html.twig` | Modal réservation avec Payment Element |
| `templates/stripe/processing.html.twig` | Page intermédiaire 2e paiement (caution) |

## Tâche en attente : support multi-devises EUR / BRL

La devise est actuellement **hardcodée à `'eur'`** dans `StripeController.php` ligne ~117.

**Ce qu'il faut faire :**
1. Ajouter un sélecteur EUR / BRL dans le modal (`details.html.twig`)
2. Passer la devise choisie en param lors de l'appel à `/stripe/create-payment-intent`
3. Ajouter un taux de conversion configurable dans `.env` (ex: `BRL_RATE=5.50`)
4. Dans `StripeController::createPaymentIntent()` : convertir le montant si BRL, passer la devise au `PaymentIntent::create()`
5. Mettre à jour `processing.html.twig` pour propager la devise au 2e paiement (caution)
6. L'entité `Payment` a déjà une colonne `currency` — elle sera utilisée automatiquement

**Note Stripe** : un `PaymentIntent` accepte une seule devise. Stripe ne convertit pas automatiquement. La conversion est à gérer côté backend.

Estimation : ~1h à 1h30 de dev.

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

## Lancer le projet

```bash
docker-compose up -d          # démarrer les conteneurs
# App : http://localhost:8001
# phpMyAdmin : http://localhost:8080
```

## Gestion caution (backoffice admin)

Après le séjour, l'admin peut depuis le backoffice :
- **Rembourser intégralement** : client reçoit le montant caution (sans les frais Stripe)
- **Rembourser partiellement** : saisir le montant des dégâts
- **Conserver la caution** : en cas de gros dégâts
