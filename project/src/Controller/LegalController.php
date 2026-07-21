<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/{_locale}/politique-de-confidentialite', name: 'app.legal.privacy', requirements: ['_locale' => 'fr|en'])]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/{_locale}/mentions-legales', name: 'app.legal.mentions', requirements: ['_locale' => 'fr|en'])]
    public function mentions(): Response
    {
        return $this->render('legal/mentions.html.twig');
    }

    #[Route('/{_locale}/cgv', name: 'app.legal.cgv', requirements: ['_locale' => 'fr|en'])]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }
}
