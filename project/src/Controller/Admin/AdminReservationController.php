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
        $isConserve = $request->request->get('conserve');

        if ($isConserve) {
            $reservation->setCautionConcerved(true);
            $reservation->setCautionRefunded(false);
            $em->persist($reservation);
            $em->flush();
            $this->addFlash('success', "Caution conservée.");
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }
        else{
            try {
            $intent = PaymentIntent::retrieve($cautionIntentId);
            $cautionAmount = $intent->metadata->caution_amount;
            
                Refund::create([
                    'payment_intent' => $cautionIntentId,
                    'amount' => (int)($cautionAmount * 100),
                ]);
                $this->addFlash('success', "Caution de {$cautionAmount}€ remboursée intégralement.");
                $reservation->setCautionRefunded(true);
                $em->persist($reservation);
                $em->flush();

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }
        
        
    }

    private function getPaymentData(Reservation $reservation): ?array
    {
        if (!$reservation->getRentPaymentIntentId() || !$reservation->getCautionPaymentIntentId()) {    
            return null;
        }

        try {
            $rentIntent = PaymentIntent::retrieve($reservation->getRentPaymentIntentId());
            $cautionIntent = PaymentIntent::retrieve($reservation->getCautionPaymentIntentId());

            
            if ($cautionIntent) {
                return [
                'rent_intent_id' => $rentIntent->id,
                'rent_amount' => $rentIntent->amount / 100,
                'rent_status' => $rentIntent->status,
                'caution_intent_id' => $cautionIntent->id,
                'caution_amount' => $cautionIntent->metadata->caution_amount ?? 0,
                'caution_with_fees' => $cautionIntent->amount / 100,
                'caution_status' => $cautionIntent->status,
                ];
            }
            else {
                return null;
            }

            
        } catch (\Exception $e) {
            dd($e);
            error_log('Error fetching payment data: ' . $e->getMessage());
            return null;
        }
    }
}