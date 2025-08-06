<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/user')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }


    #[Route('/', name: 'admin.user', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }


    #[Route('/{id}', name: 'admin.user.delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajax/list', name: 'admin.ajax.user')]
    public function ajaxAllUsers(Request $request): Response
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

        $user = $this->userRepository
            ->findAllFiltered(
                $page,
                $nombreMaxPage,
                $orderBy,
                $search
            );

        return new JsonResponse([
            'recordsTotal'      => $user[1],
            'recordsFiltered'   => $user[1],
            'data'              => array_map(function ($value) {
                return array_values($value);
            }, $user[0]),
        ]);
    }
}
