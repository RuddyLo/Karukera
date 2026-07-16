<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use App\Form\CouponType;
use App\Repository\CouponRedemptionRepository;
use App\Repository\CouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/coupons')]
class AdminCouponController extends AbstractController
{
    #[Route('/', name: 'coupon_index', methods: ['GET'])]
    public function index(CouponRepository $couponRepository, CouponRedemptionRepository $redemptionRepository): Response
    {
        $coupons = $couponRepository->findAll();
        $usageCounts = [];
        foreach ($coupons as $coupon) {
            $usageCounts[$coupon->getId()] = $redemptionRepository->countByCoupon($coupon);
        }

        return $this->render('admin/coupon/index.html.twig', [
            'coupons' => $coupons,
            'usage_counts' => $usageCounts,
        ]);
    }

    #[Route('/new', name: 'coupon_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $coupon = new Coupon();
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($coupon);
            $entityManager->flush();

            $this->addFlash('success', 'Coupon créé avec succès.');

            return $this->redirectToRoute('coupon_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/coupon/new.html.twig', [
            'coupon' => $coupon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'coupon_show', methods: ['GET'])]
    public function show(Coupon $coupon, CouponRedemptionRepository $redemptionRepository): Response
    {
        return $this->render('admin/coupon/show.html.twig', [
            'coupon' => $coupon,
            'usage_count' => $redemptionRepository->countByCoupon($coupon),
        ]);
    }

    #[Route('/{id}/edit', name: 'coupon_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Coupon $coupon, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Coupon mis à jour avec succès.');

            return $this->redirectToRoute('coupon_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/coupon/edit.html.twig', [
            'coupon' => $coupon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'coupon_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, Coupon $coupon, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-active'.$coupon->getId(), $request->request->get('_token'))) {
            $coupon->setIsActive(!$coupon->isActive());
            $entityManager->flush();
        }

        return $this->redirectToRoute('coupon_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'coupon_delete', methods: ['POST'])]
    public function delete(Request $request, Coupon $coupon, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$coupon->getId(), $request->request->get('_token'))) {
            $entityManager->remove($coupon);
            $entityManager->flush();
        }

        return $this->redirectToRoute('coupon_index', [], Response::HTTP_SEE_OTHER);
    }
}
