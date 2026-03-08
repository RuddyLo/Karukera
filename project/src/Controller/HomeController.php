<?php

namespace App\Controller;

use App\Repository\ApartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SearchFormType;

class HomeController extends AbstractController
{
    public function __construct(
        private ApartmentRepository $apartmentRepository,
    ) {}

    #[Route('/', name: 'root_redirect')]
    public function rootRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app.home', ['_locale' => 'fr']);
    }

    #[Route('/{_locale}/', name: 'app.home', requirements: ['_locale' => 'fr|en'])]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $apartments = $this->apartmentRepository->findBy(['is_active' => true]);
        $apartments_on_top = $this->apartmentRepository->findBy(['on_top' => true, 'is_active' => true]);
        $display_more = count($apartments) > count($apartments_on_top);
        $last_apartments = $this->apartmentRepository->findBy(
            ['is_active' => true],
            ['id' => 'DESC'],
            2
        );

        return $this->render('home/index.html.twig', [
            'apartments_on_top' => $apartments_on_top,
            'apartments' => $apartments,
            'last_apartments' => $last_apartments,
            'display_more' => $display_more,
            'controller_name' => 'HomeController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{_locale}/search', name: 'app.search_apartment', requirements: ['_locale' => 'fr|en'])]
    public function search(Request $request, ApartmentRepository $repo): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $data = $form->getData();

        $results = $repo->searchApartments(
            $data['name'] ?? null,
            $data['startDate'] ?? null,
            $data['endDate'] ?? null,
        );

        $apartments = $this->apartmentRepository->findBy(['is_active' => true]);

        return $this->render('search/results.html.twig', [
            'results' => $results,
            'filters' => $data,
            'apartments' => $apartments,
        ]);
    }

    #[Route('/{_locale}/contact', name: 'app.contact', requirements: ['_locale' => 'fr|en'])]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}