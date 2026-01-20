Workflow complet:
1. Réservation (côté client):

Client sélectionne dates sur calendrier
Clique "Faire ma réservation"
Modal s'ouvre avec récap:

Location: X€
Caution: Y€
Frais Stripe: ~Z€
Total caution à payer: Y + Z€


2 formulaires de paiement Stripe apparaissent:

Formulaire 1: Paiement location
Formulaire 2: Paiement caution + frais


Client entre infos carte (ou utilise la même carte pour les 2)
Clique "Payer maintenant"

2. Traitement paiement:

Intent 1 (location): Capturé immédiatement → argent sur ton compte
Redirection vers page intermédiaire
Intent 2 (caution): Capturé automatiquement → argent sur ton compte
Redirection vers page succès
Réservation créée en BDD

3. Pendant le séjour:

Les 2 montants sont sur ton compte Stripe
Client profite de l'appartement

4. Après le séjour (backoffice admin):

Tu consultes la liste des réservations terminées
Pour chaque réservation, tu vois:

Montant location: X€ (conservé)
Montant caution: Y€ (remboursable)
PaymentIntent ID de la caution


Scénario A - Aucun dégât:

Clique "Rembourser caution intégralement"
Client reçoit Y€ (pas les frais Stripe)


Scénario B - Dégâts partiels:

Entre montant des dégâts (ex: 50€)
Clique "Rembourser partiellement"
Client reçoit Y - 50€


Scénario C - Gros dégâts:

Ne fais rien ou clique "Conserver la caution"
Client ne reçoit rien



Résultat:

Tu gardes toujours la location
Tu contrôles le remboursement de la caution
Les frais Stripe (~5€) sont à la charge du client