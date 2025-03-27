<?php

namespace App\Controller;

use App\Entity\Apartment;
use App\Repository\ApartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function details(Apartment $apartment): Response
    {
        
        return $this->render('apartments/details.html.twig', [
            'apartment' => $apartment,
        ]);
    }
}
