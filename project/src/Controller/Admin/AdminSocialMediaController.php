<?php

namespace App\Controller\Admin;

use App\Entity\SocialMedia;
use App\Form\SocialMediaType;
use App\Repository\SocialMediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/social-media')]
class AdminSocialMediaController extends AbstractController
{
    #[Route('/', name: 'admin.social_media', methods: ['GET'])]
    public function index(SocialMediaRepository $socialMediaRepository): Response
    {
        return $this->render('admin/social_media/index.html.twig', [
            'social_medias' => $socialMediaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin.social_media.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SocialMediaRepository $socialMediaRepository): Response
    {
        $socialMedia = new SocialMedia();
        $form = $this->createForm(SocialMediaType::class, $socialMedia, [
            'platform_choices' => $this->getAvailablePlatformChoices($socialMediaRepository),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($socialMedia);
            $entityManager->flush();

            $this->addFlash('success', 'Le réseau social a été créé avec succès');
            return $this->redirectToRoute('admin.social_media', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/social_media/new.html.twig', [
            'social_media' => $socialMedia,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.social_media.show', methods: ['GET'])]
    public function show(SocialMedia $socialMedia): Response
    {
        return $this->render('admin/social_media/show.html.twig', [
            'social_media' => $socialMedia,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.social_media.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SocialMedia $socialMedia, EntityManagerInterface $entityManager, SocialMediaRepository $socialMediaRepository): Response
    {
        $form = $this->createForm(SocialMediaType::class, $socialMedia, [
            'platform_choices' => $this->getAvailablePlatformChoices($socialMediaRepository, $socialMedia),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le réseau social a été modifié avec succès');
            return $this->redirectToRoute('admin.social_media', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/social_media/edit.html.twig', [
            'social_media' => $socialMedia,
            'form' => $form,
        ]);
    }

    #[Route('/delete', name: 'admin.social_media.delete', methods: ['POST'])]
    public function delete(Request $request, SocialMediaRepository $socialMediaRepository): Response
    {
        if ($this->isCsrfTokenValid('delete-social-media-karukera', $request->request->get('csrf-token'))) {
            $id = $request->request->get('id');
            $socialMedia = $socialMediaRepository->find($id);
            if ($socialMedia) {
                $socialMediaRepository->remove($socialMedia, true);
                $this->addFlash('success', 'Le réseau social a été supprimé avec succès');
            }
        }

        return $this->redirectToRoute('admin.social_media', [], Response::HTTP_SEE_OTHER);
    }

    private function getAvailablePlatformChoices(SocialMediaRepository $socialMediaRepository, ?SocialMedia $current = null): array
    {
        $choices = [
            'Facebook' => 'Facebook',
            'Instagram' => 'Instagram',
            'Twitter' => 'Twitter',
            'TikTok' => 'TikTok',
            'YouTube' => 'YouTube',
            'LinkedIn' => 'LinkedIn',
        ];

        foreach ($socialMediaRepository->findAll() as $socialMedia) {
            if ($current && $socialMedia->getId() === $current->getId()) {
                continue;
            }
            unset($choices[$socialMedia->getPlatform()]);
        }

        return $choices;
    }

    #[Route('/ajax/list', name: 'admin.ajax.social_media', methods: ['GET'])]
    public function ajaxList(Request $request, SocialMediaRepository $socialMediaRepository): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => 'method not allowed'], 403);
        }

        $socialMedias = $socialMediaRepository->findAll();

        $data = array_map(function (SocialMedia $socialMedia) {
            return [
                $socialMedia->getId(),
                $socialMedia->getPlatform(),
                $socialMedia->getUrl(),
                $socialMedia->getIcon(),
                '<a href="' . $this->generateUrl('admin.social_media.show', ['id' => $socialMedia->getId()]) . '" class="btn btn-sm btn-info">Voir</a>
                 <a href="' . $this->generateUrl('admin.social_media.edit', ['id' => $socialMedia->getId()]) . '" class="btn btn-sm btn-warning">Modifier</a>',
            ];
        }, $socialMedias);

        return new JsonResponse([
            'recordsTotal' => count($socialMedias),
            'recordsFiltered' => count($socialMedias),
            'data' => $data,
        ]);
    }
}
