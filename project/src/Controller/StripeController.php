<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ApartmentRepository;
use App\Repository\PricePeriodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    private const CAUTION_RATE = 0.30;
    private const STRIPE_FEE_RATE = 0.015;
    private const STRIPE_FEE_FIXED = 0.25;

    #[Route('/stripe/create-admin-reservation', name: 'stripe_create_admin_reservation', methods: ['POST'])]
    public function createAdminReservation(
        Request $request,
        ApartmentRepository $apartmentRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);

        $apartment = $apartmentRepository->find($data['apartment_id']);
        if (!$apartment) {
            return new JsonResponse(['error' => 'Apartment not found'], 404);
        }

        $startDate = new \DateTime($data['start_date']);
        $endDate   = new \DateTime($data['end_date']);

        // Check for existing reservation in same period
        $existingReservation = $em->getRepository(Reservation::class)
            ->findOneBy([
                'user' => $this->getUser(),
                'apartment' => $apartment,
                'startDate' => $startDate
            ]);

        if ($existingReservation) {
            return new JsonResponse(['error' => 'Une réservation existe déjà pour cette période'], 400);
        }

        try {
            $reservation = new Reservation();
            $reservation->setApartment($apartment);
            $reservation->setUser($this->getUser());
            $reservation->setStartDate($startDate);
            $reservation->setEndDate($endDate);
            $reservation->setConfirmed(true);
            $reservation->setStatus('confirmed');
            $reservation->setCautionConcerved(false);
            $reservation->setCautionRefunded(false);
            $reservation->setReference($reservation->generateReference());
            
            $em->persist($reservation);
            $em->flush();

            $this->sendReservationConfirmationEmails($reservation, $mailer);

            return new JsonResponse([
                'success' => true,
                'message' => 'Réservation créée avec succès (mode admin)',
                'reservation_id' => $reservation->getId(),
                'reference' => $reservation->getReference()
            ]);
        } catch (\Exception $e) {
            error_log('Admin reservation creation error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la création de la réservation'], 500);
        }
    }

    #[Route('/stripe/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(
        Request $request,
        ApartmentRepository $apartmentRepository,
        PricePeriodRepository $pricePeriodRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $data = json_decode($request->getContent(), true);

        $apartment = $apartmentRepository->find($data['apartment_id']);
        if (!$apartment) {
            return new JsonResponse(['error' => 'Apartment not found'], 404);
        }

        $startDate = new \DateTime($data['start_date']);
        $endDate   = new \DateTime($data['end_date']);
        $days      = max(1, $startDate->diff($endDate)->days);

        $pricePeriod = $pricePeriodRepository->findCurrentPricePeriod($startDate, $apartment);
        $price = $pricePeriod ? (float) $pricePeriod->getPrice() : (float) $apartment->getPrice();

        $rentAmount  = $price * $days;
        $caution     = $days <= 3 ? 400.0 : 500.0;
        $cautionWithFees = round($caution + ($caution * self::STRIPE_FEE_RATE) + self::STRIPE_FEE_FIXED, 2);

        $paymentIntentRent = PaymentIntent::create([
            'amount' => (int) ($rentAmount * 100) + (int) ($cautionWithFees * 100),
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'type' => 'rent',
                'user_id' => $this->getUser()->getId(),
                'apartment_id' => $apartment->getId(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'rent_amount' => $rentAmount,
                'caution_amount' => $caution,
                'caution_with_fees' => $cautionWithFees,
                'days' => $days,
            ],
        ]);

        return new JsonResponse([
            'clientSecretRent' => $paymentIntentRent->client_secret,
            'rentAmount' => $rentAmount,
            'cautionAmount' => $caution,
            'cautionWithFees' => $cautionWithFees,
            'stripeFees' => round($cautionWithFees - $caution, 2),
            'days' => $days,
        ]);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $em,
        ApartmentRepository $apartmentRepository,
        MailerInterface $mailer
    ): Response {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $this->getParameter('stripe_webhook_secret');

        if ($endpointSecret) {
            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } catch (\UnexpectedValueException $e) {
                return new Response('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                return new Response('Invalid signature', 400);
            }
        } else {
            $event = json_decode($payload, false);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $meta = $intent->metadata;

            if ($meta->type === 'caution') {
                $rentIntentId = $meta->rent_payment_intent;
                $rentIntent = PaymentIntent::retrieve($rentIntentId);
                $rentMeta = $rentIntent->metadata;

                $apartment = $apartmentRepository->find($rentMeta->apartment_id);
                $user = $em->getRepository(\App\Entity\User::class)->find($rentMeta->user_id);

                if ($apartment && $user) {
                    $existing = $em->getRepository(Reservation::class)
                        ->findOneBy([
                            'user' => $user, 
                            'apartment' => $apartment, 
                            'startDate' => new \DateTime($rentMeta->start_date)
                        ]);
                    
                    if (!$existing) {
                        $reservation = new Reservation();
                        $reservation->setApartment($apartment);
                        $reservation->setUser($user);
                        $reservation->setStartDate(new \DateTime($rentMeta->start_date));
                        $reservation->setEndDate(new \DateTime($rentMeta->end_date));
                        $reservation->setConfirmed(true);
                        $reservation->setRentPaymentIntentId($rentIntentId);
                        $reservation->setCautionPaymentIntentId($intent->id);
                        $reservation->setCautionConcerved(false);
                        $reservation->setCautionRefunded(false);
                        $reservation->setReference($reservation->generateReference());
                        $em->persist($reservation);
                        $em->flush();

                        $this->sendReservationConfirmationEmails($reservation, $mailer);
                        error_log('Webhook: Reservation created - ID: ' . $reservation->getId());
                    }
                }
            }
        }

        return new Response('OK', 200);
    }


    #[Route('/payment/success', name: 'payment_success')]
    public function success(
        Request $request,
        EntityManagerInterface $em,
        ApartmentRepository $apartmentRepository,
        MailerInterface $mailer
    ): Response {
        $paymentIntentId = $request->query->get('payment_intent');
        $meta = null;
        
        if ($paymentIntentId) {
            Stripe::setApiKey($this->getParameter('stripe_secret_key'));
            
            try {
                $intent = PaymentIntent::retrieve($paymentIntentId);
                
                if ($intent->status === 'succeeded') {
                    
                    
                    $meta = $intent->metadata;
                        
                    $apartment = $apartmentRepository->find($meta->apartment_id);
                    $user = $em->getRepository(\App\Entity\User::class)->find($meta->user_id);
                    
                    if ($apartment && $user) {
                        $existing = $em->getRepository(Reservation::class)
                            ->findOneBy([
                                'user' => $user, 
                                'apartment' => $apartment, 
                                'startDate' => new \DateTime($meta->start_date)
                            ]);
                        
                        if (!$existing) {
                            $reservation = new Reservation();
                            $reservation->setApartment($apartment);
                            $reservation->setUser($user);
                            $reservation->setStartDate(new \DateTime($meta->start_date));
                            $reservation->setEndDate(new \DateTime($meta->end_date));
                            $reservation->setConfirmed(true);
                            $reservation->setRentPaymentIntentId($paymentIntentId);
                            $reservation->setCautionPaymentIntentId($paymentIntentId);
                            $reservation->setCautionConcerved(false);
                            $reservation->setCautionRefunded(false);
                            $reservation->setReference($reservation->generateReference());
                            $em->persist($reservation);
                            $em->flush();

                            $this->sendReservationConfirmationEmails($reservation, $mailer);
                        }
                    }
                    
                }
            } catch (\Exception $e) {
                error_log('Payment success error: ' . $e->getMessage());
            }
        }

        if ($meta !== null && isset($meta->apartment_id)) {
            $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');
            return $this->redirectToRoute('app.apartment.details', [
                'id' => $meta->apartment_id
            ]);
        } else {
            $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');
            return $this->redirectToRoute('app.home');
        }
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé.');
        return $this->redirectToRoute('app.home');
    }

    private function sendReservationConfirmationEmails(Reservation $reservation, MailerInterface $mailer): void
    {
        $userEmail = $reservation->getUser()?->getEmail();
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'contact@oasiskarurio.com';

        if (!$userEmail) {
            return;
        }

        $firstName     = $reservation->getUser()?->getFirstName() ?? 'Client';
        $apartmentName = $reservation->getApartment()->getName();
        $startDate     = $reservation->getStartDate()->format('d/m/Y');
        $endDate       = $reservation->getEndDate()->format('d/m/Y');
        $reference     = $reservation->getReference() ?? 'N/A';

        $rentAmount    = 0;
        $cautionAmount = 0;
        $total         = 0;

        if ($reservation->getRentPaymentIntentId()) {
            try {
                $intent        = \Stripe\PaymentIntent::retrieve($reservation->getRentPaymentIntentId());
                $rentAmount    = (float) ($intent->metadata->rent_amount ?? 0);
                $cautionAmount = (float) ($intent->metadata->caution_amount ?? 0);
                $cautionFees   = (float) ($intent->metadata->caution_with_fees ?? $cautionAmount);
                $total         = $rentAmount + $cautionFees;
            } catch (\Exception) {}
        }

        $from = new Address($_ENV['MAILER_FROM_ADDRESS'] ?? 'no-reply@oasiskarurio.com', 'Oasis de Karurio');

        $clientSubject = 'Confirmation de votre réservation – Oasis de Karurio';
        $clientHtml = sprintf(
            '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#333;">
  <div style="background:#2c7a4b;padding:24px 32px;border-radius:8px 8px 0 0;">
    <h1 style="color:#fff;margin:0;font-size:22px;">Oasis de Karurio</h1>
  </div>
  <div style="padding:32px;border:1px solid #e0e0e0;border-top:none;border-radius:0 0 8px 8px;">
    <p style="font-size:16px;">Bonjour <strong>%s</strong>,</p>
    <p>Nous avons le plaisir de confirmer votre réservation pour votre séjour en Guadeloupe.</p>

    <table style="width:100%%;border-collapse:collapse;margin:24px 0;background:#f9f9f9;border-radius:6px;">
      <tr><td colspan="2" style="padding:12px 16px;background:#2c7a4b;color:#fff;border-radius:6px 6px 0 0;font-weight:bold;">📋 Détails de la réservation</td></tr>
      <tr><td style="padding:10px 16px;border-bottom:1px solid #eee;width:40%%;">Référence</td><td style="padding:10px 16px;border-bottom:1px solid #eee;"><strong>%s</strong></td></tr>
      <tr><td style="padding:10px 16px;border-bottom:1px solid #eee;">📍 Appartement</td><td style="padding:10px 16px;border-bottom:1px solid #eee;"><strong>%s</strong></td></tr>
      <tr><td style="padding:10px 16px;border-bottom:1px solid #eee;">📅 Arrivée</td><td style="padding:10px 16px;border-bottom:1px solid #eee;"><strong>%s</strong> à partir de 15h00</td></tr>
      <tr><td style="padding:10px 16px;border-bottom:1px solid #eee;">📅 Départ</td><td style="padding:10px 16px;border-bottom:1px solid #eee;"><strong>%s</strong> avant 11h00</td></tr>
      <tr><td style="padding:10px 16px;border-bottom:1px solid #eee;">💳 Montant du séjour</td><td style="padding:10px 16px;border-bottom:1px solid #eee;"><strong>%s €</strong></td></tr>
      <tr><td style="padding:10px 16px;">💰 Total payé</td><td style="padding:10px 16px;"><strong>%s €</strong></td></tr>
    </table>

    <div style="background:#fff8e1;border-left:4px solid #f9a825;padding:16px 20px;margin:24px 0;border-radius:0 6px 6px 0;">
      <p style="margin:0 0 8px;font-weight:bold;">🔒 Dépôt de garantie : %s €</p>
      <p style="margin:0;font-size:14px;color:#555;">Une empreinte bancaire de <strong>%s €</strong> a été prélevée. Elle vous sera remboursée sous 48h suivant votre check-out, sauf en cas de dommage, non-respect du règlement intérieur ou frais supplémentaires constatés après le départ.</p>
    </div>

    <div style="background:#f5f5f5;padding:16px 20px;border-radius:6px;margin:24px 0;">
      <p style="margin:0 0 10px;font-weight:bold;">📌 Rappel des principales règles :</p>
      <ul style="margin:0;padding-left:20px;color:#555;font-size:14px;line-height:1.8;">
        <li>Logement non-fumeur</li>
        <li>Fêtes et événements interdits</li>
        <li>Voyageurs supplémentaires non autorisés</li>
        <li>Respect du voisinage et du calme</li>
      </ul>
    </div>

    <p style="font-size:14px;color:#555;">Le règlement intérieur et les conditions de réservation acceptés lors du paiement s\'appliquent à l\'ensemble du séjour.</p>
    <p style="font-size:14px;color:#555;">Les informations d\'arrivée et l\'accès au logement vous seront envoyés avant votre check-in.</p>
    <p>Nous restons disponibles pour toute question et vous souhaitons un excellent séjour en Guadeloupe.</p>

    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="margin:0;font-size:13px;color:#888;">
      <strong>Oasis de Karukera</strong><br>
      +33 755 50 30 86<br>
      <a href="mailto:contact@oasiskarurio.com" style="color:#2c7a4b;">contact@oasiskarurio.com</a>
    </p>
  </div>
</div>',
            htmlspecialchars($firstName),
            htmlspecialchars($reference),
            htmlspecialchars($apartmentName),
            $startDate,
            $endDate,
            number_format($rentAmount, 2, ',', ' '),
            number_format($total, 2, ',', ' '),
            number_format($cautionAmount, 2, ',', ' '),
            number_format($cautionAmount, 2, ',', ' ')
        );

        $adminSubject = 'Nouvelle réservation – ' . $apartmentName . ' (' . $startDate . ' → ' . $endDate . ')';
        $adminHtml = sprintf(
            '<p>Une nouvelle réservation a été confirmée.</p>
             <ul>
               <li>Référence : <strong>%s</strong></li>
               <li>Appartement : <strong>%s</strong></li>
               <li>Période : <strong>%s → %s</strong></li>
               <li>Client : <strong>%s %s</strong> (%s)</li>
               <li>Séjour : <strong>%s €</strong></li>
               <li>Caution : <strong>%s €</strong></li>
               <li>Total : <strong>%s €</strong></li>
             </ul>',
            htmlspecialchars($reference),
            htmlspecialchars($apartmentName),
            $startDate,
            $endDate,
            htmlspecialchars($firstName),
            htmlspecialchars($reservation->getUser()?->getLastName() ?? ''),
            htmlspecialchars($userEmail),
            number_format($rentAmount, 2, ',', ' '),
            number_format($cautionAmount, 2, ',', ' '),
            number_format($total, 2, ',', ' ')
        );

        try {
            $mailer->send((new Email())
                ->from($from)
                ->to($userEmail)
                ->subject($clientSubject)
                ->html($clientHtml)
            );

            $mailer->send((new Email())
                ->from($from)
                ->to($adminEmail)
                ->subject($adminSubject)
                ->html($adminHtml)
            );
        } catch (\Exception $e) {
            error_log('Reservation confirmation email error: ' . $e->getMessage());
        }
    }
}