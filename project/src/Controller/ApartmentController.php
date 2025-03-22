<?php

namespace App\Controller;

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
}
