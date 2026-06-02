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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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

    #[Route('/{id}/send-review-invite', name: 'admin_reservation_send_review', methods: ['POST'])]
public function sendReviewInvite(
    Reservation $reservation,
    Request $request,
    MailerInterface $mailer
): Response {
    if (!$this->isCsrfTokenValid('send_review' . $reservation->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Jeton CSRF invalide.');
        return $this->redirectToRoute('admin_reservations_list');
    }

    $user = $reservation->getUser();
    $emailAddress = $user?->getEmail();

    if (!$emailAddress) {
        $this->addFlash('error', 'Impossible d\'envoyer l\'email : adresse introuvable.');
        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    $reviewUrl = 'https://fr.trustpilot.com/review/oasiskarurio.com';
    $subject = 'Merci pour votre séjour - laissez-nous un avis Trustpilot';
    $htmlContent = sprintf(
        '<p>Bonjour,</p><p>Merci d\'avoir séjourné avec nous. Nous serions ravis que vous laissiez un avis sur Trustpilot.</p><p><a href="%s" target="_blank" rel="noopener">Laisser un avis</a></p><p>Merci encore et à bientôt,</p><p>Oasis de Karurio</p>',
        $reviewUrl
    );

    try {
        $email = (new Email())
            ->from(new Address($_ENV['MAILER_FROM_ADDRESS'] ?? 'no-reply@oasiskarurio.com', 'Oasis de Karurio'))
            ->to($emailAddress)
            ->bcc('oasiskarurio.com+fae2506764@invite.trustpilot.com')
            ->subject($subject)
            ->html($htmlContent);

        $mailer->send($email);
        $this->addFlash('success', 'Email de demande d\'avis envoyé au client.');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    }

    return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
}

    #[Route('/{id}/refund-caution', name: 'admin_refund_caution', methods: ['POST'])]
    public function refundCaution(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $cautionIntentId = $request->request->get('caution_intent_id');
        $isConserve = $request->request->get('conserve');

        if ($isConserve) {
            $reservation->setCautionConcerved(true);
            $reservation->setCautionRefunded(false);
            $em->persist($reservation);
            $em->flush();

            $this->sendCautionNotificationEmails($reservation, false, $mailer);
            $this->addFlash('success', "Caution conservée.");
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }

        try {
            $intent = PaymentIntent::retrieve($cautionIntentId);
            $cautionAmount = $intent->metadata->caution_amount;
            $currency = $intent->metadata->currency ?? 'eur';
            $symbol = $currency === 'brl' ? 'R$' : '€';

            Refund::create([
                'payment_intent' => $cautionIntentId,
                'amount' => (int)($cautionAmount * 100),
            ]);

            $reservation->setCautionRefunded(true);
            $reservation->setCautionConcerved(false);
            $em->persist($reservation);
            $em->flush();

            $this->sendCautionNotificationEmails($reservation, true, $mailer);
            $this->addFlash('success', "Caution de {$cautionAmount} {$symbol} remboursée intégralement.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    private function sendCautionNotificationEmails(Reservation $reservation, bool $isRefunded, MailerInterface $mailer): void
    {
        $user = $reservation->getUser();
        $userEmail = $user?->getEmail();
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'contact@oasiskarurio.com';

        if (!$userEmail) {
            return;
        }

        $statusLabel = $isRefunded ? 'remboursée' : 'conservée';
        $subjectClient = $isRefunded ? 'Votre caution a été remboursée' : 'Votre caution a été conservée';
        $subjectAdmin = $isRefunded ? 'Caution remboursée pour une réservation' : 'Caution conservée pour une réservation';

        $bodyClient = sprintf(
            '<p>Bonjour,</p><p>Votre caution pour la réservation de l’appartement <strong>%s</strong> du %s au %s a été %s.</p><p>Merci.</p>',
            $reservation->getApartment()->getName(),
            $reservation->getStartDate()->format('d/m/Y'),
            $reservation->getEndDate()->format('d/m/Y'),
            $statusLabel
        );

        $bodyAdmin = sprintf(
            '<p>Réservation #%d : la caution a été %s.</p><p>Client : %s</p><p>Appartement : %s</p><p>Période : %s → %s</p>',
            $reservation->getId(),
            $statusLabel,
            $userEmail,
            $reservation->getApartment()->getName(),
            $reservation->getStartDate()->format('d/m/Y'),
            $reservation->getEndDate()->format('d/m/Y')
        );

        $from = new Address($_ENV['MAILER_FROM_ADDRESS'] ?? 'no-reply@oasiskarurio.com', 'Oasis de Karurio');

        try {
            $mailer->send((new Email())
                ->from($from)
                ->to($userEmail)
                ->subject($subjectClient)
                ->html($bodyClient)
            );

            $mailer->send((new Email())
                ->from($from)
                ->to($adminEmail)
                ->subject($subjectAdmin)
                ->html($bodyAdmin)
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l’envoi des emails de suivi : ' . $e->getMessage());
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
                $currency = $rentIntent->metadata->currency ?? 'eur';
                return [
                'rent_intent_id' => $rentIntent->id,
                'rent_amount' => $rentIntent->metadata->rent_amount ?? $rentIntent->amount / 100,
                'rent_status' => $rentIntent->status,
                'caution_intent_id' => $cautionIntent->id,
                'caution_amount' => $cautionIntent->metadata->caution_amount ?? 0,
                'caution_with_fees' => $cautionIntent->amount / 100,
                'caution_status' => $cautionIntent->status,
                'currency' => $currency,
                'currency_symbol' => $currency === 'brl' ? 'R$' : '€',
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