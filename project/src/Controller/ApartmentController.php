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
        $apartments = $this->apartmentRepository->findBy(['is_active' => true]);
        return $this->render('apartments/apartments.html.twig', [
            'apartments' => $apartments,
        ]);
    }

    #[Route('/{id}/details', name: 'app.apartment.details')]
    public function details(Apartment $apartment, Request $request,EntityManagerInterface $em,
         ReservationValidationService $validationService): Response
    {
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
                return $this->redirectToRoute('app.apartment.details', ['id' => $apartment->getId()]);
            } else {
                $em->persist($reservation);
                $em->flush();
                $this->addFlash('success', 'Réservation enregistrée ! Vous pouvez consulter la liste de vos réservations dans votre compte.');
                return $this->redirectToRoute('app.apartment.details', ['id' => $apartment->getId()]);
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

    #[Route('/{id}/reservations/json', name: 'reservations_json')]
    public function reservationsJson(ReservationRepository $repo, Apartment $apartment): JsonResponse
    {
        $reservations = $repo->findBy(['apartment' => $apartment]);
        $events = [];

        foreach ($reservations as $reservation) {
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
        $minimumStayPeriods = $this->minimumStayPeriodRepository->findByApartment($apartment);
        $events = [];

        foreach ($minimumStayPeriods as $minimum) {
            $events[] = [
                'title' => $minimum->getMinimumDays() . ' j. min',
                'start' => $minimum->getStartDate()->format('Y-m-d'),
                'end'   => $minimum->getEndDate()->format('Y-m-d'),
                'color' => '#f39c12',
                'display' => 'background',
                'extendedProps' => [
                    'minimumDays' => $minimum->getMinimumDays(),
                ],
            ];
        }

        return $this->json($events);
    }
      
}
