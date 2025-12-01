<?php

namespace App\Controller;

use App\Repository\ApartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{
    public function __construct(
        private ApartmentRepository $apartmentRepository,
    ) {}
    #[Route('/', name: 'app.home')]
    public function index(): Response
    {
        $apartments = $this->apartmentRepository->findBy(['is_active' => true]);
        $apartments_on_top = $this->apartmentRepository->findBy(['on_top' => true, 'is_active' => true]);
        $display_more = false;

        if (count($apartments) > count($apartments_on_top)) {
            $display_more = true;
        }
        return $this->render('home/index.html.twig', [
            'apartments_on_top' => $apartments_on_top,
            'apartments' => $apartments,
            'display_more' => $display_more,
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/search', name: 'app.search_apartment', methods: ['GET'])]
    public function search(Request $request, ApartmentRepository $repo)
    {
        $apartmentName = $request->query->get('apartment');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $guests = (int) $request->query->get('guests');

        // You can build custom search logic here:
        $results = $repo->searchApartments($apartmentName, $startDate, $endDate);

        return $this->render('search/results.html.twig', [
            'results' => $results,
            'filters' => [
                'apartment' => $apartmentName,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'guests' => $guests,
            ]
        ]);
    }
}
