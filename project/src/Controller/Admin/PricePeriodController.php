<?php

namespace App\Controller\Admin;

use App\Entity\Apartment;
use App\Entity\PricePeriod;
use App\Form\PricePeriodType;
use App\Repository\ApartmentRepository;
use App\Repository\PricePeriodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/price/period')]
class PricePeriodController extends AbstractController
{
    #[Route('/', name: 'price_period_index', methods: ['GET'])]
    public function index(PricePeriodRepository $pricePeriodRepository): Response
    {
        return $this->render('admin/price_period/index.html.twig', [
            'price_periods' => $pricePeriodRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'price_period_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pricePeriod = new PricePeriod();
        $form = $this->createForm(PricePeriodType::class, $pricePeriod);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pricePeriod);
            $entityManager->flush();

            return $this->redirectToRoute('price_period_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/price_period/new.html.twig', [
            'price_period' => $pricePeriod,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'price_period_show', methods: ['GET'])]
    public function show(PricePeriod $pricePeriod): Response
    {
        return $this->render('admin/price_period/show.html.twig', [
            'price_period' => $pricePeriod,
        ]);
    }

    #[Route('/{id}/edit', name: 'price_period_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PricePeriod $pricePeriod, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PricePeriodType::class, $pricePeriod);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('price_period_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/price_period/edit.html.twig', [
            'price_period' => $pricePeriod,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'price_period_delete', methods: ['POST'])]
    public function delete(Request $request, PricePeriod $pricePeriod, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pricePeriod->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pricePeriod);
            $entityManager->flush();
        }

        return $this->redirectToRoute('price_period_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/pricePeriode/{id}/json', name: 'pridePeriode.json')]
    public function pricePeriodJson(PricePeriodRepository $repo, ApartmentRepository $apartmentRepository, Apartment $apartment): JsonResponse
    {
        
        $pricePeriods = $repo->findBy(['apartment'=>$apartment]);
        $events = [];

        foreach ($pricePeriods as $price) {
            $events[] = [
                'title' => $price->getPrice()."€",
                'start' => $price->getStartDate()->format('Y-m-d'),
                'end'   => $price->getEndDate()->format('Y-m-d'), 
                'color' => '#b66b0aff',
                'url'   => $this->generateUrl('price_period_edit', [
                    'id' => $price->getId()
                ]),
            ];
        }

        return $this->json($events);
    }
}
