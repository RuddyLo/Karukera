2. Utilisateur sélectionne les dates

Clique sur le calendrier FullCalendar
Les dates remplissent les champs startDate et endDate

3. Utilisateur clique "Faire ma réservation"

reservation.js vérifie que les dates sont remplies
Si OK, envoie requête POST vers /stripe/create-payment-intent
Reçoit: clientSecret, rentAmount, cautionAmount, totalAmount, days
Affiche le modal avec le récap
Monte le Payment Element Stripe

4. Modal s'ouvre

Affiche le récapitulatif (dates, nombre de nuits, location, caution, total)
Le Payment Element Stripe est visible
Utilisateur entre ses infos de carte

5. Utilisateur clique "Payer maintenant"

stripe.confirmPayment() est appelé
Stripe traite le paiement
Si succès → redirection automatique vers /payment/success?payment_intent=pi_xxx
Si erreur → affiche le message d'erreur

6. Page de succès (/payment/success)

Récupère le payment_intent depuis l'URL
Vérifie que le paiement est succeeded via l'API Stripe
Crée la réservation en BDD directement (fallback car webhook ne marche pas en local)
Affiche le message de confirmation
Redirige vers la home

7. (En parallèle) Webhook Stripe (ne fonctionne pas en local HTTP)

Stripe essaie d'envoyer payment_intent.succeeded
Échoue car pas d'URL publique
Pas grave, la réservation est déjà créée à l'étape 6