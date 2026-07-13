<?php

namespace App\Controller;

use App\Entity\News;
use App\Form\ContactFormType;
use App\Repository\ApartmentRepository;
use App\Repository\Blog\ArticleRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SearchFormType;
use App\Repository\NewsRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class HomeController extends AbstractController
{
    public function __construct(
        private ApartmentRepository $apartmentRepository,
        private ArticleRepository $articleRepository,
        private NewsRepository $newsRepository,
        private ReservationRepository $reservationRepository,
    ) {}

    #[Route('/', name: 'root_redirect')]
    public function rootRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app.home', ['_locale' => 'fr']);
    }

    #[Route('/{_locale}/', name: 'app.home', requirements: ['_locale' => 'fr|en'])]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $apartments = $this->apartmentRepository->findBy(['is_active' => true, 'is_deleted' => false]);
        $apartments_on_top = $this->apartmentRepository->findBy(['on_top' => true, 'is_active' => true, 'is_deleted' => false]);
        $display_more = count($apartments) > count($apartments_on_top);
        $last_apartments = $this->apartmentRepository->findBy(
            ['is_active' => true, 'is_deleted' => false],
            ['id' => 'DESC'],
            2
        );

        $lastArticles = $this->articleRepository->findPublishedArticles(3);
        $news = $this->newsRepository->findOneBy(['active' => true]);

        return $this->render('home/index.html.twig', [
            'apartments_on_top' => $apartments_on_top,
            'news' => $news,
            'apartments' => $apartments,
            'last_apartments' => $last_apartments,
            'display_more' => $display_more,
            'controller_name' => 'HomeController',
            'form' => $form->createView(),
             'last_articles' => $lastArticles,
        ]);
    }
    #[Route('/{_locale}/search', name: 'app.search_apartment', requirements: ['_locale' => 'fr|en'])]
public function search(Request $request, ApartmentRepository $repo): Response
{
    $form = $this->createForm(SearchFormType::class);
    $form->handleRequest($request);

    $name      = null;
    $startDate = null;
    $endDate   = null;

    if ($form->isSubmitted()) {
        $data      = $form->getData();
        $name      = $data['name'] ?? null;
        $startDate = $data['startDate'] ?? null;
        $endDate   = $data['endDate'] ?? null;
    }

    $results     = $repo->searchApartments($name);
    $conflictIds = [];
    if ($startDate && $endDate) {
        $conflictIds = $this->reservationRepository->findOverlappingApartmentIds($startDate, $endDate);
    }
    $apartments = $this->apartmentRepository->findBy(['is_active' => true, 'is_deleted' => false]);

    return $this->render('search/results.html.twig', [
        'results'     => $results,
        'conflictIds' => $conflictIds,
        'filters'     => compact('name', 'startDate', 'endDate'),
        'apartments'  => $apartments,
    ]);
}

    
    #[Route('/{_locale}/contact', name: 'app.contact', requirements: ['_locale' => 'fr|en'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from(new Address($_ENV['MAILER_FROM_ADDRESS'], 'Oasis Karurio'))
                ->to($_ENV['ADMIN_EMAIL'])
                ->replyTo($data['email'])
                ->subject('[Contact] ' . $data['subject'])
                ->html(
                    '<p><strong>Nom :</strong> ' . $data['name'] . '</p>' .
                    '<p><strong>Email :</strong> ' . $data['email'] . '</p>' .
                    '<p><strong>Message :</strong><br>' . nl2br($data['message']) . '</p>' .
                    ($data['start_date'] ? '<p><strong>Arrivée :</strong> ' . $data['start_date']->format('d/m/Y') . '</p>' : '') .
                    ($data['end_date'] ? '<p><strong>Départ :</strong> ' . $data['end_date']->format('d/m/Y') . '</p>' : '')
                );

            try {
                $mailer->send($email);
                $this->addFlash('success', 'contact.flash.success');
            } catch (\Exception $e) {
                $this->addFlash('danger', $e->getMessage());
            }

            return $this->redirectToRoute('app.contact', ['_locale' => $request->getLocale()]);
        }

        return $this->render('components/contact.html.twig', [
            'form' => $form->createView(),
            'recaptcha_key' => $_ENV['RECAPTCHA3_KEY'],
        ]);
    }

#[Route('/test-mail', name: 'app.test_mail')]
public function testMail(MailerInterface $mailer): Response
{
    $email = (new Email())
        ->from('d5f74dfdb4-12c54f+user1@inbox.mailtrap.io')
        ->to('d5f74dfdb4-12c54f+user1@inbox.mailtrap.io') // ton email Mailtrap autorisé
        ->subject('Test Mailtrap Symfony')
        ->text('Ceci est un test depuis Symfony avec Mailtrap.');

    try {
        $mailer->send($email);
        return new Response('Mail envoyé avec succès.');
    } catch (\Throwable $e) {
        return new Response('Erreur : ' . $e->getMessage());
    }
}
}