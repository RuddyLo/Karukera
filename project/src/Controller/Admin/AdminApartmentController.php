<?php

namespace App\Controller\Admin;

use App\Entity\Apartment;
use App\Form\ApartmentFormType;
use App\Repository\ApartmentRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/admin/apartment')]
class AdminApartmentController extends AbstractController
{
    private Slugify $slugify;
    private String $apartmentImageDirectory;
    private String $imageUrlDirectory;
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApartmentRepository $apartmentRepository,
        private readonly ParameterBagInterface $parameterBag,
    ){  
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

            if ($form->get('imageUrl')->getData()) {
                if (!file_exists($this->apartmentImageDirectory)) {
                    mkdir($this->apartmentImageDirectory, 0777, true);
                }
                $image = $form->all()['imageUrl']->getData();

                $image
                    ->move(
                        $this->apartmentImageDirectory,
                        $this->slugify->slugify(
                            $image->getClientOriginalName()
                        )
                    );
                // set image

                $apartment
                    ->setImageUrl(
                        $this->imageUrlDirectory . $this->slugify->slugify($image->getClientOriginalName())
                    );
            }


            $entityManager->persist($apartment);
            $entityManager->flush();

            return $this->redirectToRoute('admin.apartment', []);
        }

        return $this->render('admin/apartment/new.html.twig', [
            'apartment' => $apartment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.apartment.show', methods: ['GET'])]
    public function show(Apartment $apartment): Response
    {
        return $this->render('admin/apartment/show.html.twig', [
            'apartment' => $apartment,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.apartment.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Apartment $apartment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApartmentFormType::class, $apartment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('imageUrl')->getData()) {
                if (!file_exists($this->apartmentImageDirectory)) {
                    mkdir($this->apartmentImageDirectory, 0777, true);
                }
                $image = $form->all()['imageUrl']->getData();

                $image
                    ->move(
                        $this->apartmentImageDirectory,
                        $this->slugify->slugify(
                            $image->getClientOriginalName()
                        )
                    );
                // set image

                $apartment
                    ->setImageUrl(
                        $this->imageUrlDirectory . $this->slugify->slugify($image->getClientOriginalName())
                    );
            }
            $entityManager->flush();

            return $this->redirectToRoute('admin.apartment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/apartment/edit.html.twig', [
            'apartment' => $apartment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.apartment.delete', methods: ['POST'])]
    public function delete(Request $request, Apartment $apartment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$apartment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($apartment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin.apartment', []);
    }

    #[Route('/ajax/list', name: 'admin.ajax.apartment')]
    public function ajaxAllProgrammes(Request $request): Response
    {
        
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(
                [
                'message' => 'method not allowed',],
                403
            );
        }

        $page           = $request->get('start');
        $nombreMaxPage  = $request->get('length');
        $search         = $request->get('search')['value'] ?? '';
        $orderBy        = $request->get('order_by');

        $apartment = $this->apartmentRepository
            ->findAllFiltered(
                $page,
                $nombreMaxPage,
                $orderBy,
                $search
            );

            return new JsonResponse([
                'recordsTotal'      => $apartment[1],
                'recordsFiltered'   => $apartment[1],
                'data'              => array_map(function ($value) {
                    return array_values($value);
                }, $apartment[0]),
            ]);
    }


}
