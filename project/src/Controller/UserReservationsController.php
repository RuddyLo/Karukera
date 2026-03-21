<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UserReservationsController extends AbstractController
{
    #[Route('/{_locale}/my-reservations', name: 'app.user.reservations', requirements: ['_locale' => 'fr|en'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $reservations = $reservationRepository->findBy(
            ['user' => $this->getUser()],
            ['startDate' => 'DESC']
        );

        $reservationsWithPayments = [];
        foreach ($reservations as $reservation) {
            $paymentData = null;

            if ($reservation->getRentPaymentIntentId() && $reservation->getCautionPaymentIntentId()) {
                try {
                    $rentIntent    = PaymentIntent::retrieve($reservation->getRentPaymentIntentId());
                    $cautionIntent = PaymentIntent::retrieve($reservation->getCautionPaymentIntentId());

                    $paymentData = [
                        'rent_amount'    => $rentIntent->amount / 100,
                        'rent_status'    => $rentIntent->status,
                        'caution_amount' => $cautionIntent->metadata->caution_amount ?? 0,
                        'total'          => ($rentIntent->amount / 100) + ($cautionIntent->metadata->caution_amount ?? 0),
                    ];
                } catch (\Exception $e) {
                    $paymentData = null;
                }
            }

            $reservationsWithPayments[] = [
                'reservation' => $reservation,
                'payment'     => $paymentData,
            ];
        }

        return $this->render('user/reservations.html.twig', [
            'reservations' => $reservationsWithPayments,
        ]);
    }
}