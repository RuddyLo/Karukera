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
    private const DEPOSIT_RATE = 0.30; // 30% deposit

    /**
     * STEP 1 — Create PaymentIntent (Deposit only)
     */
    #[Route('/stripe/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(
        Request $request,
        ApartmentRepository $apartmentRepository
    ): JsonResponse {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $data = json_decode($request->getContent(), true);

        $apartment = $apartmentRepository->find($data['apartment_id']);
        if (!$apartment) {
            return new JsonResponse(['error' => 'Apartment not found'], 404);
        }

        // 🔐 Server-side price calculation (MANDATORY)
        $startDate = new \DateTime($data['start_date']);
        $endDate   = new \DateTime($data['end_date']);
        $days      = max(1, $startDate->diff($endDate)->days);

        $totalAmount  = $apartment->getPrice() * $days;
        $deposit      = round($totalAmount * self::DEPOSIT_RATE, 2);

        $paymentIntent = PaymentIntent::create([
            'amount' => (int) ($deposit * 100), // cents
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'capture_method' => 'automatic', // change to 'manual' if needed
            'metadata' => [
                'apartment_id' => $apartment->getId(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_amount' => $totalAmount,
                'deposit_amount' => $deposit,
            ],
        ]);

        return new JsonResponse([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    /**
     * STEP 2 — Stripe Webhook (Payment confirmed)
     */
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

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception) {
            return new Response('Invalid webhook', 400);
        }

        /**
         * 🔔 Deposit paid successfully
         */
        if ($event->type === 'payment_intent.succeeded') {
            /** @var PaymentIntent $intent */
            $intent = $event->data->object;
            $meta = $intent->metadata;

            $apartment = $apartmentRepository->find($meta->apartment_id);
            if (!$apartment) {
                return new Response('Apartment not found', 200);
            }

            $reservation = new Reservation();
            $reservation->setApartment($apartment);
            $reservation->setUser(null); // set logged user if available
            $reservation->setStartDate(new \DateTime($meta->start_date));
            $reservation->setEndDate(new \DateTime($meta->end_date));
            $reservation->setConfirmed(true);

            /**
             * 🔥 STRONGLY RECOMMENDED FIELDS (add in entity)
             */
            // $reservation->setStripePaymentIntentId($intent->id);
            // $reservation->setTotalAmount($meta->total_amount);
            // $reservation->setDepositAmount($meta->deposit_amount);
            // $reservation->setPaymentStatus('deposit_paid');

            $em->persist($reservation);
            $em->flush();
        }

        return new Response('Webhook handled', 200);
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function success(): Response
    {
        return $this->render('stripe/success.html.twig');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig');
    }
}
