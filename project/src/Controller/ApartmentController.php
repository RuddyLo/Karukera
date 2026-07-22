<?php

namespace App\Controller;

use App\Entity\Apartment;
use App\Entity\Reservation;
use App\Form\ReservationFormType;
use App\Repository\ApartmentRepository;
use App\Repository\PricePeriodRepository;
use App\Repository\MinimumStayPeriodRepository;
use App\Repository\ReservationRepository;
use App\Service\ReservationValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}/apartments', requirements: ['_locale' => 'fr|en'])]
class ApartmentController extends AbstractController
{
    public function __construct(
        private ApartmentRepository $apartmentRepository,
        private PricePeriodRepository $repo,
        private MinimumStayPeriodRepository $minimumStayPeriodRepository,
    ) {
    }


    #[Route('/', name: 'app.apartments')]
    public function index(): Response
    {
        $apartments = $this->apartmentRepository->findBy(['is_active' => true, 'is_deleted' => false]);
        return $this->render('apartments/apartments.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    #[Route('/{id}/details', name: 'app.apartment.details')]
    public function details(Apartment $apartment, Request $request,EntityManagerInterface $em,
         ReservationValidationService $validationService): Response
    {
        if ($apartment->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $reservation = new Reservation();
        $reservation->setApartment($apartment);
        $reservation->setUser($this->getUser());

        $form = $this->createForm(ReservationFormType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $validationService->validate($reservation);
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
                return $this->redirectToRoute('app.apartment.details', ['id' => $apartment->getId(), '_locale' => $request->getLocale()]);
            } else {
                $em->persist($reservation);
                $em->flush();
                $this->addFlash('success', 'Réservation enregistrée ! Vous pouvez consulter la liste de vos réservations dans votre compte.');
                return $this->redirectToRoute('app.apartment.details', ['id' => $apartment->getId(), '_locale' => $request->getLocale()]);
            }
        }

        $pricePeriod = $this->repo->findCurrentPricePeriod(null,$apartment);

        $price = $pricePeriod
            ? $pricePeriod->getPrice()
            : $apartment->getPrice();

        return $this->render('apartments/details.html.twig', [
            'form' => $form->createView(),
            'apartment' => $apartment,
            'price' => $price,
            'stripe_public_key' => $this->getParameter('stripe_publishable_key'),
        ]);
    }

    #[Route('/{id}/price-for-date', name: 'app.apartment.price_for_date')]
    public function priceForDate(Apartment $apartment, Request $request): JsonResponse
    {
        if ($apartment->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $dateStr    = $request->query->get('date');
        $endDateStr = $request->query->get('end');
        $date       = $dateStr ? new \DateTime($dateStr) : new \DateTime('today');

        if ($endDateStr) {
            $endDate = new \DateTime($endDateStr);
            $stay    = $this->repo->calculateStayPrice($apartment, $date, $endDate);

            $lastNight = (clone $endDate)->modify('-1 day');
            $startPeriod = $this->repo->findCurrentPricePeriod($date, $apartment);
            $endPeriod   = $this->repo->findCurrentPricePeriod($lastNight, $apartment);
            $uniformPeriod = ($startPeriod && $endPeriod && $startPeriod->getId() === $endPeriod->getId())
                ? $startPeriod
                : null;

            return $this->json([
                'price'       => round($stay['pricePerNight'], 2),
                'total'       => round($stay['total'], 2),
                'nights'      => $stay['nights'],
                'breakdown'   => $stay['breakdown'],
                'groupedBreakdown' => $stay['groupedBreakdown'],
                'hasPeriod'   => round($stay['pricePerNight'], 2) !== round((float) $apartment->getPrice(), 2),
                'periodStart' => $uniformPeriod?->getStartDate()?->format('d/m/Y'),
                'periodEnd'   => $uniformPeriod?->getEndDate()?->format('d/m/Y'),
            ]);
        }

        $pricePeriod = $this->repo->findCurrentPricePeriod($date, $apartment);
        $price = $pricePeriod ? (float) $pricePeriod->getPrice() : (float) $apartment->getPrice();

        return $this->json([
            'price'       => $price,
            'hasPeriod'   => $pricePeriod !== null,
            'periodStart' => $pricePeriod?->getStartDate()?->format('d/m/Y'),
            'periodEnd'   => $pricePeriod?->getEndDate()?->format('d/m/Y'),
        ]);
    }

    #[Route('/{id}/reservations/json', name: 'reservations_json')]
    public function reservationsJson(ReservationRepository $repo, Apartment $apartment): JsonResponse
    {
        if ($apartment->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $reservations = $repo->createQueryBuilder('r')
            ->andWhere('r.apartment = :apartment')
            ->andWhere('r.status != :canceled')
            ->setParameter('apartment', $apartment)
            ->setParameter('canceled', 'canceled')
            ->getQuery()
            ->getResult();
        $events = [];

        foreach ($reservations as $reservation) {
            // FullCalendar utilise end EXCLUSIF : endDate (jour de checkout) redevient disponible en check-in
            $events[] = [
                'title' => 'Réservé',
                'start' => $reservation->getStartDate()->format('Y-m-d'),
                'end'   => $reservation->getEndDate()->format('Y-m-d'),
                'color' => '#ff4d4d', // rouge
            ];
        }

        return $this->json($events);
    }

    #[Route('/{id}/minimumStay/json', name: 'minimumStay_json')]
    public function minimumStayJson(Apartment $apartment): JsonResponse
    {
        if ($apartment->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $minimumStayPeriods = $this->minimumStayPeriodRepository->findByApartment($apartment);
        $events = [];

        foreach ($minimumStayPeriods as $minimum) {
            $events[] = [
                
                'start' => $minimum->getStartDate()->format('Y-m-d'),
                'end'   => $minimum->getEndDate()->format('Y-m-d'),
                'backgroundColor' => '#cdf1e4c7',
                'display' => 'background',
                'extendedProps' => [
                    'minimumDays' => $minimum->getMinimumDays(),
                ],
            ];
        }

        return $this->json($events);
    }
      
}
