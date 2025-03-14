<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApartmentController extends AbstractController
{
    #[Route('/apartments', name: 'app.apartments')]
    public function index(): Response
    {
        return $this->render('apartments/apartments.html.twig', [
            'controller_name' => 'ApartmentController',
        ]);
    }
}
