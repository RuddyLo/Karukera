<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class RegistrationController extends AbstractController
{
    #[Route('/{_locale}/register', name: 'app.register', requirements: ['_locale' => 'fr|en'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Génération du token de vérification
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $user->setIsVerified(false);

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Lien de vérification
                $verificationUrl = $this->generateUrl(
                    'app.verify_email',
                    ['token' => $token, '_locale' => $request->getLocale()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                // Email à l'utilisateur
                $emailUser = (new Email())
                    ->from(new Address($_ENV['MAILER_FROM_ADDRESS'], 'Oasis Karurio'))
                    ->to($user->getEmail())
                    ->subject('Confirmez votre inscription')
                    ->html(
                        '<h2>Bienvenue sur Oasis Karurio !</h2>' .
                        '<p>Cliquez sur le lien ci-dessous pour confirmer votre adresse email :</p>' .
                        '<a href="' . $verificationUrl . '" style="background:#28a745;color:white;padding:12px 24px;border-radius:5px;text-decoration:none;">Confirmer mon email</a>' .
                        '<p>Ce lien est valable 24h.</p>'
                    );
                $mailer->send($emailUser);

                // Email à l'admin
                $emailAdmin = (new Email())
                    ->from(new Address($_ENV['MAILER_FROM_ADDRESS'], 'Oasis Karurio'))
                    ->to($_ENV['ADMIN_EMAIL'])
                    ->subject('Nouvelle inscription : ' . $user->getEmail())
                    ->html(
                        '<h2>Nouvelle inscription</h2>' .
                        '<p><strong>Email :</strong> ' . $user->getEmail() . '</p>'
                    );
                $mailer->send($emailAdmin);

                $this->addFlash('success', 'Un email de confirmation vous a été envoyé.');
                return $this->redirectToRoute('app.login', ['_locale' => $request->getLocale()]);

            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('danger', 'Cette adresse email est déjà utilisée.');
            }
        }

        return $this->render('register.html.twig', [
            'registrationForm' => $form->createView(),
            'recaptcha_key' => $_ENV['RECAPTCHA3_KEY'],
        ]);
    }

    #[Route('/{_locale}/verify-email/{token}', name: 'app.verify_email', requirements: ['_locale' => 'fr|en'])]
    public function verifyEmail(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app.login', ['_locale' => 'fr']);
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

        $this->addFlash('success', 'Votre email est confirmé, vous pouvez vous connecter !');
        return $this->redirectToRoute('app.login', ['_locale' => 'fr']);
    }
}