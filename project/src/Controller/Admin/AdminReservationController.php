<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/reservations')]
class AdminReservationController extends AbstractController
{
    #[Route('', name: 'admin_reservations_list')]
    public function list(): Response
    {
        return $this->render('admin/reservations/list.html.twig');
    }

    #[Route('/ajax/list', name: 'admin.ajax.reservation')]
    public function ajaxList(Request $request, ReservationRepository $reservationRepository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => 'method not allowed'], 403);
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $page         = (int) $request->get('start', 0);
        $length       = (int) $request->get('length', 10);
        $search       = $request->get('search')['value'] ?? '';
        $orderBy      = $request->get('order_by');
        $statusFilter = $request->get('status_filter', '');

        [$reservations, $total] = $reservationRepository->findAllFiltered($page, $length, $orderBy, $search, $statusFilter);

        $now = new \DateTime('today');
        $data = [];
        foreach ($reservations as $reservation) {
            $payment = $this->getPaymentData($reservation);

            if ($reservation->getStatus() === 'canceled') {
                $statusKey = 'canceled';
            } elseif ($reservation->getEndDate() < $now) {
                $statusKey = 'finished';
            } elseif ($reservation->getStartDate() > $now) {
                $statusKey = 'upcoming';
            } else {
                $statusKey = 'ongoing';
            }

            $data[] = [
                $reservation->getId(),
                $reservation->getReference(),
                $reservation->getUser()?->getEmail(),
                $reservation->getApartment()?->getName(),
                $reservation->getStartDate()->format('d/m/Y') . ' → ' . $reservation->getEndDate()->format('d/m/Y'),
                $payment ? number_format((float) $payment['rent_amount'], 2) . ' ' . $payment['currency_symbol'] : null,
                [
                    'status' => $reservation->isCautionRefunded()
                        ? 'refunded'
                        : ($reservation->isCautionConcerved() ? 'conserved' : 'pending'),
                    'amount' => $payment ? number_format((float) $payment['caution_amount'], 2) . ' ' . $payment['currency_symbol'] : null,
                ],
                $statusKey,
                $statusKey === 'finished',
                $csrfTokenManager->getToken('send_review' . $reservation->getId())->getValue(),
            ];
        }

        return new JsonResponse([
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ]);
    }

    #[Route('/{id}/cancel', name: 'admin_reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('cancel_reservation', $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
            }
            return $this->redirectToRoute('admin_reservations_list');
        }

        // Annulation douce : la réservation est conservée (paiements Stripe, coupon utilisé),
        // mais libère les dates dans le calendrier public.
        $reservation->setStatus('canceled');
        $reservation->setConfirmed(false);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirectToRoute('admin_reservations_list');
    }

    #[Route('/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(Reservation $reservation, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_reservation', $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
            }
            return $this->redirectToRoute('admin_reservations_list');
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirectToRoute('admin_reservations_list');
    }

    #[Route('/{id}', name: 'admin_reservation_show', requirements: ['id' => '\d+'])]
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

            if ((float) $cautionAmount > 0) {
                Refund::create([
                    'payment_intent' => $cautionIntentId,
                    'amount' => (int)($cautionAmount * 100),
                ]);
            }

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
            error_log('Error fetching payment data: ' . $e->getMessage());
            return null;
        }
    }
}