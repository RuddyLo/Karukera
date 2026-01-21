<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations')]
class AdminReservationController extends AbstractController
{
    #[Route('', name: 'admin_reservations_list')]
    public function list(ReservationRepository $reservationRepository): Response
    {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $reservations = $reservationRepository->findBy([], ['startDate' => 'DESC']);
        
        $reservationsWithPayments = [];
        foreach ($reservations as $reservation) {
            $paymentData = $this->getPaymentData($reservation);
            dd($paymentData);
            $reservationsWithPayments[] = [
                'reservation' => $reservation,
                'payment' => $paymentData
            ];
            
        }
        
        return $this->render('admin/reservations/list.html.twig', [
            'reservations' => $reservationsWithPayments,
        ]);
    }

    #[Route('/{id}', name: 'admin_reservation_show')]
    public function show(Reservation $reservation): Response
    {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $paymentData = $this->getPaymentData($reservation);

        return $this->render('admin/reservations/show.html.twig', [
            'reservation' => $reservation,
            'payment' => $paymentData,
        ]);
    }

    #[Route('/{id}/refund-caution', name: 'admin_refund_caution', methods: ['POST'])]
    public function refundCaution(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $cautionIntentId = $request->request->get('caution_intent_id');
        $refundType = $request->request->get('refund_type');
        $partialAmount = $request->request->get('partial_amount');

        try {
            $intent = PaymentIntent::retrieve($cautionIntentId);
            $cautionAmount = $intent->metadata->caution_amount;

            if ($refundType === 'full') {
                Refund::create([
                    'payment_intent' => $cautionIntentId,
                    'amount' => (int)($cautionAmount * 100),
                ]);
                $this->addFlash('success', "Caution de {$cautionAmount}€ remboursée intégralement.");
            } elseif ($refundType === 'partial' && $partialAmount) {
                $amountToRefund = min((float)$partialAmount, (float)$cautionAmount);
                Refund::create([
                    'payment_intent' => $cautionIntentId,
                    'amount' => (int)($amountToRefund * 100),
                ]);
                $this->addFlash('success', "Remboursement partiel de {$amountToRefund}€ effectué.");
            } else {
                $this->addFlash('warning', 'Caution conservée (aucun remboursement).');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    private function getPaymentData(Reservation $reservation): ?array
    {
        if (!$reservation->getRentPaymentIntentId() || !$reservation->getCautionPaymentIntentId()) {    
            return null;
        }

        try {
            $rentIntent = PaymentIntent::retrieve($reservation->getRentPaymentIntentId());
            $cautionIntent = PaymentIntent::retrieve($reservation->getCautionPaymentIntentId());
            dd($rentIntent, $cautionIntent);

            $cautionRefunds = [];
            if ($cautionIntent) {
                foreach ($cautionIntent->charges->data as $charge) {
                    if ($charge->refunds->data) {
                        foreach ($charge->refunds->data as $refund) {
                            $cautionRefunds[] = [
                                'amount' => $refund->amount / 100,
                                'date' => date('d/m/Y H:i', $refund->created),
                                'status' => $refund->status,
                            ];
                        }
                    }
                }
            }

            return [
                'rent_intent_id' => $rentIntent->id,
                'rent_amount' => $rentIntent->amount / 100,
                'rent_status' => $rentIntent->status,
                'caution_intent_id' => $cautionIntent->id,
                'caution_amount' => $cautionIntent->metadata->caution_amount ?? 0,
                'caution_with_fees' => $cautionIntent->amount / 100,
                'caution_status' => $cautionIntent->status,
                'caution_refunds' => $cautionRefunds,
                'caution_refunded_total' => array_sum(array_column($cautionRefunds, 'amount')),
            ];
        } catch (\Exception $e) {
            dd($e);
            error_log('Error fetching payment data: ' . $e->getMessage());
            return null;
        }
    }
}