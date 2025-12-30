<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ApartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * Create PaymentIntent (DEPOSIT)
     */
    #[Route('/stripe/create-payment-intent', name: 'create_deposit_intent', methods: ['POST'])]
    public function createDepositIntent(
        Request $request,
        ApartmentRepository $apartmentRepository
    ): JsonResponse {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $data = json_decode($request->getContent(), true);

        $apartment = $apartmentRepository->find($data['apartment_id']);
        if (!$apartment) {
            return new JsonResponse(['error' => 'Apartment not found'], 404);
        }

        // 🔐 ALWAYS calculate server-side
        $startDate = new \DateTime($data['start_date']);
        $endDate   = new \DateTime($data['end_date']);
        $days      = max(1, $startDate->diff($endDate)->days);

        $totalAmount = $apartment->getPrice() * $days;
        $depositRate = 0.30; // 30% deposit
        $depositAmount = (int) round($totalAmount * $depositRate * 100);

        $paymentIntent = PaymentIntent::create([
            'amount' => $depositAmount,
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
                'deposit_amount' => $depositAmount / 100,
            ],
        ]);

        return new JsonResponse([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    /**
     * Stripe Webhook
     */
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $em,
        ApartmentRepository $apartmentRepository
    ): Response {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');
        $endpointSecret = $this->getParameter('stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $endpointSecret);
        } catch (\Exception $e) {
            return new Response('Invalid webhook', 400);
        }

        // ✅ Payment succeeded
        if ($event->type === 'payment_intent.succeeded') {
            /** @var PaymentIntent $intent */
            $intent = $event->data->object;

            $metadata = $intent->metadata;

            $apartment = $apartmentRepository->find($metadata->apartment_id);
            if (!$apartment) {
                return new Response('Apartment not found', 200);
            }

            $reservation = new Reservation();
            $reservation->setApartment($apartment);
            $reservation->setUser(null); // attach logged user if needed
            $reservation->setStartDate(new \DateTime($metadata->start_date));
            $reservation->setEndDate(new \DateTime($metadata->end_date));
            $reservation->setConfirmed(true);

            // Optional fields if you added them
            // $reservation->setDepositAmount($metadata->deposit_amount);
            // $reservation->setStripePaymentIntentId($intent->id);

            $em->persist($reservation);
            $em->flush();
        }

        return new Response('Webhook processed', 200);
    }

    #[Route('/payment/processing', name: 'payment_processing')]
    public function processing(): Response
    {
        return $this->render('stripe/processing.html.twig');
    }
}
