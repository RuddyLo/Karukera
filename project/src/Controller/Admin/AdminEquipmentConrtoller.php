<?php

namespace App\Controller\Admin;

use App\Entity\Equipment;
use App\Form\EquipmentFormType;
use App\Repository\EquipmentRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
        private EquipmentRepository $apartmentRepository,
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

    #[Route('/{id}', name: 'app_equipment_show', methods: ['GET'])]
    public function show(Equipment $equipment): Response
    {
        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EquipmentFormType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipment_delete', methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($equipment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
    }
}
