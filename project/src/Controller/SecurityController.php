<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;


class SecurityController extends AbstractController
{
    use TargetPathTrait;

    #[Route(path: '/{_locale}/login', name: 'app.login', requirements: ['_locale' => 'fr|en'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app.home', ['_locale' => 'fr']);
        }

        if ($targetPath = $request->query->get('_target_path')) {
            $this->saveTargetPath($request->getSession(), 'main', $targetPath);
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'recaptcha_key' => $_ENV['RECAPTCHA3_KEY'],
        ]);
    }

    #[Route(path: '/{_locale}/logout', name: 'app.logout', requirements: ['_locale' => 'fr|en'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
