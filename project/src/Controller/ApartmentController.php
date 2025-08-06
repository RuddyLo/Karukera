<?php

namespace App\Controller;

use App\Entity\Apartment;
use App\Entity\Reservation;
use App\Form\ReservationFormType;
use App\Repository\ApartmentRepository;
use App\Service\ReservationCheckerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/apartments')]
class ApartmentController extends AbstractController
{
    public function __construct(
        private ApartmentRepository $apartmentRepository
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
         ReservationCheckerService $reservationCheckerService): Response
    {
        $reservation = new Reservation();
        $reservation->setApartment($apartment);
        $reservation->setUser($this->getUser());

        $form = $this->createForm(ReservationFormType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$reservationCheckerService->isAvailable($reservation)) {
                $this->addFlash('danger', 'L\'appartement est déjà réservé sur cette période.');
                return $this->redirectToRoute('app.apartment.details', ['id' => $apartment->getId()]);
            } else {
                $em->persist($reservation);
                $em->flush();
                $this->addFlash('success', 'Réservation enregistrée !');
                return $this->redirectToRoute('app.apartments');
            }
        }
        
        
        return $this->render('apartments/details.html.twig', [
            'form' => $form->createView(),
            'apartment' => $apartment,
        ]);
    }

      
}
