<?php

namespace App\Controller;

use App\Repository\ApartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SearchFormType;

class HomeController extends AbstractController
{
    public function __construct(
        private ApartmentRepository $apartmentRepository,
    ) {}

    #[Route('/', name: 'app.home')]
    public function index(Request $request): Response
    {
        // Create the search form for the hero section
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $apartments = $this->apartmentRepository->findBy(['is_active' => true]);
        $apartments_on_top = $this->apartmentRepository->findBy(['on_top' => true, 'is_active' => true]);
        $display_more = count($apartments) > count($apartments_on_top);

        return $this->render('home/index.html.twig', [
            'apartments_on_top' => $apartments_on_top,
            'apartments' => $apartments,
            'display_more' => $display_more,
            'controller_name' => 'HomeController',

            // IMPORTANT: make the form available
            'form' => $form->createView(),
        ]);
    }

    #[Route('/search', name: 'app.search_apartment')]
    public function search(Request $request, ApartmentRepository $repo): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $results = [];
        $filters = [];

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            // The SearchFormType defines: name, startDate, endDate
            $name = $data['name'] ?? null;
            $start = $data['startDate'] ? $data['startDate']->format('Y-m-d') : null;
            $end = $data['endDate'] ? $data['endDate']->format('Y-m-d') : null;

            $results = $repo->searchApartments(
                $name,
                $start,
                $end
            );

            $filters = $data;
        }

        return $this->render('search/results.html.twig', [
            'results' => $results,
            'filters' => $filters,
            'form' => $form->createView(),
        ]);
    }
}
