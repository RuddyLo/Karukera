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
    $apartment = new Apartment();
    $form = $this->createForm(ApartmentFormType::class, $apartment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Image principale
        $imageFile = $form->get('imageUrl')->getData();
        if ($imageFile) {
            if (!file_exists($this->apartmentImageDirectory)) {
                mkdir($this->apartmentImageDirectory, 0777, true);
            }

            $fileName = uniqid() . '.' . $this->slugify->slugify(
                $imageFile->getClientOriginalName()
            );
            $imageFile->move($this->apartmentImageDirectory, $fileName);
            $apartment->setImageUrl($this->imageUrlDirectory . $fileName);
        } else {
            $apartment->setImageUrl($this->imageUrlDirectory . 'default.jpg');
        }

        // Images uploadées via AJAX
        $this->moveTempImages($request->request->all('temp_image_keys'), $apartment, $entityManager);

        // Locale
        $locale = $form->get('locale')->getData();
        $apartment->setTranslatableLocale($locale);
        $entityManager->persist($apartment);

        // PricePeriod (optionnel)
        $pricePeriod = $form->get('pricePeriod')->getData();
        if ($pricePeriod instanceof PricePeriod) {
            $pricePeriod->setApartment($apartment);
            $entityManager->persist($pricePeriod);
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin.apartment');
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

            // Images uploadées via AJAX
            $this->moveTempImages($request->request->all('temp_image_keys'), $apartment, $entityManager);

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
        if (!$this->isCsrfTokenValid('delete_apartment', $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
            }
            return $this->redirectToRoute('admin.apartment');
        }

        // Soft-delete : on conserve l'appartement (lié aux réservations existantes),
        // on le retire simplement de l'affichage public.
        $apartment->setIsDeleted(true);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirectToRoute('admin.apartment');
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

    #[Route('/images/upload-temp', name: 'admin.apartment.image.upload_temp', methods: ['POST'])]
    public function uploadTemp(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            $error = $file ? 'Fichier trop volumineux (limite serveur dépassée)' : 'Aucun fichier reçu';
            return new JsonResponse(['error' => $error], 400);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return new JsonResponse(['error' => 'Format non supporté (PNG, JPG, WEBP)'], 400);
        }

        $tempDir = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/images/temp/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $baseName = uniqid() . '_' . $this->slugify->slugify(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
        );

        $savedFilename = $this->compressAndSave($file->getPathname(), $file->getMimeType(), $tempDir, $baseName);

        return new JsonResponse([
            'success' => true,
            'tempKey' => $savedFilename,
            'previewUrl' => '/uploads/images/temp/' . $savedFilename,
        ]);
    }

    private function compressAndSave(string $srcPath, string $mimeType, string $destDir, string $baseName): string
    {
        $filename = $baseName . '.jpg';

        if (!$srcPath || !\function_exists('imagecreatefromjpeg')) {
            \copy($srcPath, $destDir . $filename);
            return $filename;
        }

        $imageInfo = @\getimagesize($srcPath);
        if (!$imageInfo) {
            \copy($srcPath, $destDir . $filename);
            return $filename;
        }

        // Ajuste le memory_limit selon la taille réelle (4 bytes/pixel × 2 buffers + 64 Mo overhead)
        $neededMb = (int)(($imageInfo[0] * $imageInfo[1] * 8) / 1024 / 1024) + 64;
        \ini_set('memory_limit', \max(256, $neededMb) . 'M');

        try {
            $src = match(true) {
                \in_array($mimeType, ['image/jpeg', 'image/jpg']) => @\imagecreatefromjpeg($srcPath),
                $mimeType === 'image/png'  => @\imagecreatefrompng($srcPath),
                $mimeType === 'image/webp' => @\imagecreatefromwebp($srcPath),
                default => null,
            };

            if (!$src) {
                \copy($srcPath, $destDir . $filename);
                return $filename;
            }

            $origWidth  = \imagesx($src);
            $origHeight = \imagesy($src);
            $maxWidth   = 1920;

            if ($origWidth > $maxWidth) {
                $ratio     = $maxWidth / $origWidth;
                $newWidth  = $maxWidth;
                $newHeight = (int)($origHeight * $ratio);
            } else {
                $newWidth  = $origWidth;
                $newHeight = $origHeight;
            }

            $dst   = \imagecreatetruecolor($newWidth, $newHeight);
            $white = \imagecolorallocate($dst, 255, 255, 255);
            \imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $white);

            \imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            \imagejpeg($dst, $destDir . $filename, 85);

            \imagedestroy($src);
            \imagedestroy($dst);
        } catch (\Throwable) {
            \copy($srcPath, $destDir . $filename);
        }

        return $filename;
    }

    private function moveTempImages(array $tempKeys, Apartment $apartment, EntityManagerInterface $entityManager): void
    {
        $tempDir = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/images/temp/';

        foreach ($tempKeys as $tempKey) {
            $tempPath = $tempDir . $tempKey;
            if (!file_exists($tempPath)) {
                continue;
            }

            if (!file_exists($this->apartmentImageDirectory)) {
                mkdir($this->apartmentImageDirectory, 0777, true);
            }

            rename($tempPath, $this->apartmentImageDirectory . $tempKey);

            $image = new Image();
            $image->setUrl($this->imageUrlDirectory . $tempKey);
            $apartment->addImage($image);
            $entityManager->persist($image);
        }
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