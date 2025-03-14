<?php

namespace App\Controller;

use App\Repository\ApartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApartmentRepository $apartmentRepository,
        ){}
    #[Route('/', name: 'app.home')]
    public function index(): Response
    {
        $apartments= $this->apartmentRepository->findBy(['is_active' => true]);
        $apartments_on_top = $this->apartmentRepository->findBy(['on_top' => true, 'is_active' => true]);
        $display_more = false;

        if (count($apartments) > count($apartments_on_top) ) {
            $display_more = true;
        }
        return $this->render('home/index.html.twig', [
            'apartments_on_top' => $apartments_on_top,
            'display_more'=>$display_more,
            'controller_name' => 'HomeController',
        ]);
    }
}
