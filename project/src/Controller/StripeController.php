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
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    private const CAUTION_RATE = 0.30;

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
        $days      = max(1, $startDate->diff($endDate)->days);

        $rentAmount  = $apartment->getPrice() * $days;
        $caution     = round($apartment->getPrice() * self::CAUTION_RATE, 2);
        $totalAmount = $rentAmount + $caution;

        $paymentIntent = PaymentIntent::create([
            'amount' => (int) ($totalAmount * 100),
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'user_id' => $this->getUser()->getId(),
                'apartment_id' => $apartment->getId(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'rent_amount' => $rentAmount,
                'caution_amount' => $caution,
                'total_amount' => $totalAmount,
                'days' => $days,
            ],
        ]);

        return new JsonResponse([
            'clientSecret' => $paymentIntent->client_secret,
            'rentAmount' => $rentAmount,
            'cautionAmount' => $caution,
            'totalAmount' => $totalAmount,
            'days' => $days,
        ]);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $em,
        ApartmentRepository $apartmentRepository
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

            $apartment = $apartmentRepository->find($meta->apartment_id);
            $user = $em->getRepository(\App\Entity\User::class)->find($meta->user_id);

            if (!$apartment || !$user) {
                error_log('Webhook: Resource not found - Apartment: ' . ($apartment ? 'OK' : 'MISSING') . ', User: ' . ($user ? 'OK' : 'MISSING'));
                return new Response('OK', 200);
            }

            $reservation = new Reservation();
            $reservation->setApartment($apartment);
            $reservation->setUser($user);
            $reservation->setStartDate(new \DateTime($meta->start_date));
            $reservation->setEndDate(new \DateTime($meta->end_date));
            $reservation->setConfirmed(true);

            $em->persist($reservation);
            $em->flush();

            error_log('Webhook: Reservation created successfully - ID: ' . $reservation->getId());
        }

        return new Response('OK', 200);
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function success(
        Request $request,
        EntityManagerInterface $em,
        ApartmentRepository $apartmentRepository
    ): Response {
        $paymentIntentId = $request->query->get('payment_intent');
        
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
                            ->findOneBy(['user' => $user, 'apartment' => $apartment, 'startDate' => new \DateTime($meta->start_date)]);
                        
                        if (!$existing) {
                            $reservation = new Reservation();
                            $reservation->setApartment($apartment);
                            $reservation->setUser($user);
                            $reservation->setStartDate(new \DateTime($meta->start_date));
                            $reservation->setEndDate(new \DateTime($meta->end_date));
                            $reservation->setConfirmed(true);
                            
                            $em->persist($reservation);
                            $em->flush();
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log('Payment success error: ' . $e->getMessage());
            }
        }
        
        $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');
        return $this->redirectToRoute('app.home');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé.');
        return $this->redirectToRoute('app.home');
    }
}