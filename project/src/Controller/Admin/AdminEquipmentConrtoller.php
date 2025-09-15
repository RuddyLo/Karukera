<?php

namespace App\Controller\Admin;

use App\Entity\Equipment;
use App\Form\EquipmentFormType;
use App\Repository\EquipmentRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/equipment')]
class AdminEquipmentConrtoller extends AbstractController
{
    private Slugify $slugify;
    private String $equipmentIconDirectory;
    private String $iconUrlDirectory;
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EquipmentRepository $equipmentRepository,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->slugify = new Slugify();
        $kernelDir = $this->parameterBag->get('kernel.project_dir');
        $this->equipmentIconDirectory = $kernelDir . '/public/uploads/icons/';
        $this->iconUrlDirectory = '/uploads/icons/';
    }
    #[Route('/', name: 'admin.equipment', methods: ['GET'])]
    public function index(EquipmentRepository $equipmentRepository): Response
    {
        return $this->render('admin/equipment/index.html.twig', [
            'equipment' => $equipmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin.equipment.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentFormType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $equipment = $form->getData();
            if (!file_exists($this->equipmentIconDirectory)) {
                mkdir($this->equipmentIconDirectory, 0777, true);
            }
            $image = $form->all()['iconUrl']->getData();
            $fileName = uniqid() . '.' .  $this->slugify->slugify(
                $image->getClientOriginalName()
            );

            $image
                ->move(
                    $this->equipmentIconDirectory,
                    $fileName
                );
            // set image
            $equipment
                ->setIconUrl(
                    $this->iconUrlDirectory . $fileName
                );
            $entityManager->persist($equipment);
            $entityManager->flush();

            return $this->redirectToRoute('admin.equipment.new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }


    #[Route('/{id}/edit', name: 'admin.equipment.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EquipmentFormType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            if (!file_exists($this->equipmentIconDirectory)) {
                mkdir($this->equipmentIconDirectory, 0777, true);
            }
            $image = $form->all()['iconUrl']->getData();
            if ($image) {
                $fileName = uniqid() . '.' .  $this->slugify->slugify(
                    $image->getClientOriginalName()
                );
                $image
                    ->move(
                        $this->equipmentIconDirectory,
                        $fileName
                    );
                // set image
                $equipment
                    ->setIconUrl(
                        $this->iconUrlDirectory . $fileName
                    );
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('admin.equipment', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/delete', name: 'admin.equipment.delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = $request->get('csrf-token');
        $id = $request->get('id');
        
        
        if ($this->isCsrfTokenValid('delete-equipment-karukera', $token)) {
            $equipment = $this->equipmentRepository->findOneBy(['id' => $id]);
            if ($equipment) {
                $this->equipmentRepository->remove($equipment, true);
                $this->addFlash('success', "L'equipement a  été supprimé avec succès");
            }
        }
       

        return $this->redirectToRoute('admin.equipment', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajax/list', name: 'admin.ajax.equipment')]
    public function ajaxAllEquipment(Request $request): Response
    {

        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(
                [
                    'message' => 'method not allowed',
                ],
                403
            );
        }

        $page           = $request->get('start');
        $nombreMaxPage  = $request->get('length');
        $search         = $request->get('search')['value'] ?? '';
        $orderBy        = $request->get('order_by');

        $equipment = $this->equipmentRepository
            ->findAllFiltered(
                $page,
                $nombreMaxPage,
                $orderBy,
                $search
            );

        return new JsonResponse([
            'recordsTotal'      => $equipment[1],
            'recordsFiltered'   => $equipment[1],
            'data'              => array_map(function ($value) {
                return array_values($value);
            }, $equipment[0]),
        ]);
    }
}
