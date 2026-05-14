<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ApartmentRepository;
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
        ApartmentRepository $apartmentRepository
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
        $days      = max(1, $startDate->diff($endDate)->days + 1);
        $price = $data['price'] ?? $apartment->getPrice();

        $rentAmount  = $price * $days;
        $caution     = round($price * self::CAUTION_RATE, 2);
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

        $apartmentName = $reservation->getApartment()->getName();
        $startDate = $reservation->getStartDate()->format('d/m/Y');
        $endDate = $reservation->getEndDate()->format('d/m/Y');

        $from = new Address($_ENV['MAILER_FROM_ADDRESS'] ?? 'no-reply@oasiskarurio.com', 'Oasis de Karurio');

        $clientSubject = 'Votre réservation est confirmée';
        $clientHtml = sprintf(
            '<p>Bonjour,</p><p>Votre réservation pour l’appartement <strong>%s</strong> du %s au %s est confirmée.</p><p>Merci pour votre confiance.</p>',
            $apartmentName,
            $startDate,
            $endDate
        );

        $adminSubject = 'Nouvelle réservation confirmée';
        $adminHtml = sprintf(
            '<p>Une nouvelle réservation a été confirmée.</p><p>Réservation #%d</p><p>Appartement : %s</p><p>Période : %s → %s</p><p>Client : %s</p>',
            $reservation->getId(),
            $apartmentName,
            $startDate,
            $endDate,
            $userEmail
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