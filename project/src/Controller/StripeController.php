<?php
// src/Controller/StripeEmbeddedController.php
// src/Controller/StripeController.php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ApartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;


/*

L’utilisateur paie → Stripe crée et gère la session.

Paiement validé → Stripe envoie en arrière-plan un checkout.session.completed vers ton endpoint /stripe/webhook.

Ton code dans /stripe/webhook :

Vérifie la signature.

Lit les infos (dates, utilisateur, etc.).

Crée la réservation en base de données.

Tu renvoies un 200 OK à Stripe.

*/

class StripeController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        ApartmentRepository $apartmentRepository
    ): Response {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $payload = $request->getContent();
        $sig_header = $request->headers->get('stripe-signature');
        $endpoint_secret = $this->getParameter('stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }

        // On écoute uniquement l'événement paiement réussi
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // ⚠️ Ici, tu récupères les données envoyées au moment de createCheckoutSession
            $apartmentId = $session->metadata->apartment_id ?? null;
            $startDate = $session->metadata->start_date ?? null;
            $endDate = $session->metadata->end_date ?? null;

            if ($apartmentId && $startDate && $endDate) {
                $apartment = $apartmentRepository->find($apartmentId);

                if ($apartment) {
                    $reservation = new Reservation();
                    $reservation->setApartment($apartment);
                    $reservation->setStartDate(new \DateTime($startDate));
                    $reservation->setEndDate(new \DateTime($endDate));
                    $reservation->setConfirmed(true);
                    

                    $em->persist($reservation);
                    $em->flush();
                }
            }
        }

        return new Response('Webhook handled', 200);
    }
    #[Route('/create-checkout-session', name: 'app_stripe_checkout', methods: ['POST'])]
    public function createCheckoutSession(Request $request): JsonResponse
    {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $data = json_decode($request->getContent(), true);
        
        $startDate = new \DateTime($data['start_date']);
        $endDate   = new \DateTime($data['end_date']);

        $interval = $startDate->diff($endDate);
        $days = $interval->days;

        $pricePerDay = $data['price']; 
        $totalPrice = $pricePerDay * $days;
        $unitAmount = $totalPrice * 100;

        if ($unitAmount <=0) {
            $unitAmount = $pricePerDay * 100;
        }

        $session = Session::create([
            
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Réservation Appartement',
                    ],
                    'unit_amount' => $unitAmount, // 250,00€
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_payment_success', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_payment_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'apartment_id' => $data['apartment_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ],
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/paiement/success', name: 'app_payment_success')]
    public function success(): Response
    {
        return $this->render('stripe/success.html.twig');
    }

    #[Route('/paiement/cancel', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig');
    }
}
