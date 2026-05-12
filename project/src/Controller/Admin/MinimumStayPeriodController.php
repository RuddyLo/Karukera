<?php

namespace App\Controller\Admin;

use App\Entity\Apartment;
use App\Entity\MinimumStayPeriod;
use App\Form\MinimumStayPeriodType;
use App\Repository\ApartmentRepository;
use App\Repository\MinimumStayPeriodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/minimum/stay/period')]
class MinimumStayPeriodController extends AbstractController
{
    #[Route('/', name: 'minimum_stay_period_index', methods: ['GET'])]
    public function index(MinimumStayPeriodRepository $repository): Response
    {
        return $this->render('admin/minimum_stay_period/index.html.twig', [
            'minimum_stay_periods' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'minimum_stay_period_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $minimumStayPeriod = new MinimumStayPeriod();
        $form = $this->createForm(MinimumStayPeriodType::class, $minimumStayPeriod);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($minimumStayPeriod);
            $entityManager->flush();

            return $this->redirectToRoute('minimum_stay_period_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/minimum_stay_period/new.html.twig', [
            'minimum_stay_period' => $minimumStayPeriod,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'minimum_stay_period_show', methods: ['GET'])]
    public function show(MinimumStayPeriod $minimumStayPeriod): Response
    {
        return $this->render('admin/minimum_stay_period/show.html.twig', [
            'minimum_stay_period' => $minimumStayPeriod,
        ]);
    }

    #[Route('/{id}/edit', name: 'minimum_stay_period_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MinimumStayPeriod $minimumStayPeriod, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MinimumStayPeriodType::class, $minimumStayPeriod);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('minimum_stay_period_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/minimum_stay_period/edit.html.twig', [
            'minimum_stay_period' => $minimumStayPeriod,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'minimum_stay_period_delete', methods: ['POST'])]
    public function delete(Request $request, MinimumStayPeriod $minimumStayPeriod, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$minimumStayPeriod->getId(), $request->request->get('_token'))) {
            $entityManager->remove($minimumStayPeriod);
            $entityManager->flush();
        }

        return $this->redirectToRoute('minimum_stay_period_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/minimumStayPeriode/{id}/json', name: 'minimumStayPeriode.json')]
    public function minimumStayPeriodJson(MinimumStayPeriodRepository $repo, Apartment $apartment): JsonResponse
    {
        $minimumStayPeriods = $repo->findBy(['apartment' => $apartment]);
        $events = [];

        foreach ($minimumStayPeriods as $minimum) {
            $events[] = [
                'title' => $minimum->getMinimumDays() . ' j. min',
                'start' => $minimum->getStartDate()->format('Y-m-d'),
                'end'   => $minimum->getEndDate()->format('Y-m-d'),
                'color' => '#e74c3c',
                'url'   => $this->generateUrl('minimum_stay_period_edit', [
                    'id' => $minimum->getId()
                ]),
            ];
        }

        return new JsonResponse($events);
    }
}
