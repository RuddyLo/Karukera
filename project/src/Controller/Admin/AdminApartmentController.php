<?php

namespace App\Controller\Admin;

use App\Entity\Apartment;
use App\Entity\Image;
use App\Entity\PricePeriod;
use App\Form\ApartmentFormType;
use App\Form\PricePeriodType;
use App\Repository\ApartmentRepository;
use App\Repository\PricePeriodRepository;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use function Symfony\Component\Clock\now;

#[Route('/admin/apartment')]
class AdminApartmentController extends AbstractController
{
    private Slugify $slugify;
    private String $apartmentImageDirectory;
    private String $imageUrlDirectory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApartmentRepository $apartmentRepository,
        private PricePeriodRepository $pricePeriodRepository,
        private readonly ParameterBagInterface $parameterBag,
        private TranslatableListener $translatableListener,
    ) {
        $this->slugify = new Slugify();
        $kernelDir = $this->parameterBag->get('kernel.project_dir');
        $this->apartmentImageDirectory = $kernelDir . '/public/uploads/images/';
        $this->imageUrlDirectory = '/uploads/images/';
    }

    #[Route('/', name: 'admin.apartment', methods: ['GET'])]
    public function index(ApartmentRepository $apartmentRepository): Response
    {
        return $this->render('admin/apartment/index.html.twig');
    }

    #[Route('/new', name: 'admin.apartment.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApartmentFormType::class);
        $form->handleRequest($request);

        $pricePeriod = new PricePeriod();

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('imageUrl')->getData()) {

                $apartment = $form->getData();

                if (!file_exists($this->apartmentImageDirectory)) {
                    mkdir($this->apartmentImageDirectory, 0777, true);
                }
                $image = $form->all()['imageUrl']->getData();
                $fileName = uniqid() . '.' . $this->slugify->slugify(
                    $image->getClientOriginalName()
                );

                $image->move($this->apartmentImageDirectory, $fileName);
                $apartment->setImageUrl($this->imageUrlDirectory . $fileName);

                $images = $form->get('images')->getData();
                foreach ($images as $imageFile) {
                    $fileName = uniqid() . '.' . $this->slugify->slugify(
                        $imageFile->getClientOriginalName()
                    );

                    try {
                        $imageFile->move($this->apartmentImageDirectory, $fileName);
                    } catch (FileException $e) {
                    }

                    $image = new Image();
                    $image->setUrl($this->imageUrlDirectory . $fileName);
                    $apartment->addImage($image);
                    $entityManager->persist($image);
                }

                $pricePeriod = $form->get('pricePeriod')->getData();

                $locale = $form->get('locale')->getData();
                $apartment->setTranslatableLocale($locale);
                $entityManager->persist($apartment);
                $pricePeriod->setApartment($apartment);
                $entityManager->persist($pricePeriod);
                $entityManager->flush();
            }

            return $this->redirectToRoute('admin.apartment', []);
        }

        return $this->render('admin/apartment/new.html.twig', [
            'form' => $form,
            'apartmentId' => 0
        ]);
    }

    #[Route('/{id}', name: 'admin.apartment.show', methods: ['GET'])]
    public function show(Apartment $apartment): Response
    {
        $today = new DateTime("today");
        $pricePeriod = $this->pricePeriodRepository->findCurrentPricePeriod($today, $apartment);

        return $this->render('admin/apartment/show.html.twig', [
            'apartment' => $apartment,
            'pricePeriod' => $pricePeriod
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.apartment.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Apartment $apartment, EntityManagerInterface $entityManager): Response
    {
        // Récupère la locale depuis le query param (GET) ou le formulaire (POST)
        // Permet à l'admin de charger la bonne traduction via /edit?locale=en
        $locale = $request->query->get('locale', 'fr');

        // Force Gedmo à charger les champs traduits dans la locale choisie
        $this->translatableListener->setTranslatableLocale($locale);
        $this->translatableListener->setTranslationFallback(false);

        // Recharge l'appartement avec la bonne locale
        $entityManager->refresh($apartment);

        $form = $this->createForm(ApartmentFormType::class, $apartment, [
            'locale' => $locale,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('imageUrl')->getData()) {
                if (!file_exists($this->apartmentImageDirectory)) {
                    mkdir($this->apartmentImageDirectory, 0777, true);
                }
                $image = $form->all()['imageUrl']->getData();

                $image->move(
                    $this->apartmentImageDirectory,
                    $this->slugify->slugify($image->getClientOriginalName())
                );

                $apartment->setImageUrl(
                    $this->imageUrlDirectory . $this->slugify->slugify($image->getClientOriginalName())
                );
            }

            $images = $form->get('images')->getData();
            foreach ($images as $imageFile) {
                $fileName = uniqid() . '.' . $this->slugify->slugify(
                    $imageFile->getClientOriginalName()
                );

                try {
                    $imageFile->move($this->apartmentImageDirectory, $fileName);
                } catch (FileException $e) {
                }

                $image = new Image();
                $image->setUrl($this->imageUrlDirectory . $fileName);
                $apartment->addImage($image);
                $entityManager->persist($image);
            }

            $pricePeriod = $form->get('pricePeriod')->getData();

            if ($pricePeriod && $pricePeriod->getStartDate() != null && $pricePeriod->getEndDate() != null) {
                $pricePeriod->setApartment($apartment);
                $entityManager->persist($pricePeriod);
            }

            $locale = $form->get('locale')->getData();
            $apartment->setTranslatableLocale($locale);
            $entityManager->persist($apartment);
            $entityManager->flush();

            return $this->redirectToRoute('admin.apartment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/apartment/edit.html.twig', [
            'apartmentId' => $apartment->getId(),
            'apartment' => $apartment,
            'form' => $form,
            'currentLocale' => $locale,
        ]);
    }

    #[Route('/{id}', name: 'admin.apartment.delete', methods: ['POST'])]
    public function delete(Request $request, Apartment $apartment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $apartment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($apartment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin.apartment', []);
    }

    #[Route('/ajax/list', name: 'admin.ajax.apartment')]
    public function ajaxAllProgrammes(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => 'method not allowed'], 403);
        }

        $page          = $request->get('start');
        $nombreMaxPage = $request->get('length');
        $search        = $request->get('search')['value'] ?? '';
        $orderBy       = $request->get('order_by');

        $apartment = $this->apartmentRepository->findAllFiltered(
            $page,
            $nombreMaxPage,
            $orderBy,
            $search
        );

        return new JsonResponse([
            'recordsTotal'    => $apartment[1],
            'recordsFiltered' => $apartment[1],
            'data'            => array_map(function ($value) {
                return array_values($value);
            }, $apartment[0]),
        ]);
    }

    #[Route('/images/delete/{id}', name: 'ajax.apartment.image.delete', methods: ['DELETE'])]
    public function deleteImage(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $image = $entityManager->getRepository(Image::class)->find($id);

        if (!$image) {
            return new JsonResponse(['error' => 'Image non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $apartment = $image->getApartment();
        if ($apartment) {
            $apartment->removeImage($image);
        }

        $imagePath = '/public/' . $image->getUrl();
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $entityManager->remove($image);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Image supprimée avec succès.']);
    }
}