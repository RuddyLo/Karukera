<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Entity\NewsImage;
use App\Form\NewsFormType;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/news')]
class AdminNewsController extends AbstractController
{
    public function __construct(
        private string $newsImageDirectory,
        private string $newsImageUrlDirectory,
    ) {}

    #[Route('/', name: 'admin.news.index')]
    public function index(NewsRepository $newsRepository): Response
    {
        return $this->render('admin/news/index.html.twig', [
            'newsList' => $newsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin.news.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $news = new News();
        $form = $this->createForm(NewsFormType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $locale = $form->get('locale')->getData();
            $news->setTranslatableLocale($locale);

            $em->persist($news);

            foreach ($form->get('images')->getData() as $imageFile) {
                $fileName = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->newsImageDirectory, $fileName);

                $image = new NewsImage();
                $image->setUrl($this->newsImageUrlDirectory . $fileName);
                $news->addNewsImage($image);
                $em->persist($image);
            }

            $em->flush();
            $this->addFlash('success', 'Nouveauté créée avec succès');

            return $this->redirectToRoute('admin.news.index');
        }

        return $this->render('admin/news/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.news.edit', methods: ['GET', 'POST'])]
public function edit(News $news, Request $request, EntityManagerInterface $em): Response
{
    // GET : locale depuis l'URL, POST : locale depuis le champ hidden
    $locale = $request->isMethod('POST')
        ? $request->request->all('news_form')['locale'] ?? 'fr'
        : $request->query->get('locale', 'fr');

    $news->setTranslatableLocale($locale);
    $em->refresh($news);

    $form = $this->createForm(NewsFormType::class, $news);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $news->setTranslatableLocale($locale);
        $em->persist($news);

        foreach ($form->get('images')->getData() as $imageFile) {
            $fileName = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($this->newsImageDirectory, $fileName);

            $image = new NewsImage();
            $image->setUrl($this->newsImageUrlDirectory . $fileName);
            $news->addNewsImage($image);
            $em->persist($image);
        }

        $em->flush();
        $this->addFlash('success', 'Nouveauté mise à jour');

        return $this->redirectToRoute('admin.news.edit', [
            'id' => $news->getId(),
            'locale' => $locale,
        ]);
    }

    return $this->render('admin/news/edit.html.twig', [
        'form' => $form,
        'news' => $news,
        'locale' => $locale,
    ]);
}

    #[Route('/{id}/delete', name: 'admin.news.delete', methods: ['POST'])]
    public function delete(News $news, EntityManagerInterface $em): Response
    {
        $em->remove($news);
        $em->flush();
        $this->addFlash('success', 'Nouveauté supprimée');

        return $this->redirectToRoute('admin.news.index');
    }

    #[Route('/image/{id}/delete', name: 'admin.news.image.delete', methods: ['GET'])]
    public function deleteImage(NewsImage $image, EntityManagerInterface $em): Response
    {
        $em->remove($image);
        $em->flush();
        $this->addFlash('success', 'Image supprimée');

        return $this->redirectToRoute('admin.news.edit', ['id' => $image->getNews()->getId()]);
    }
}