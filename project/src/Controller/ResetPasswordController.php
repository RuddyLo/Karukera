<?php

namespace App\Controller;

use App\Form\NewPasswordType;
use App\Form\ResetPasswordRequestType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Address;

class ResetPasswordController extends AbstractController
{
    #[Route('/{_locale}/forgot-password', name: 'app.forgot_password', requirements: ['_locale' => 'fr|en'])]
    public function request(Request $request, UserRepository $userRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user  = $userRepository->findOneBy(['email' => $email]);

            // Toujours afficher le même message pour ne pas révéler si l'email existe
            if ($user) {
                $token     = bin2hex(random_bytes(32));
                $expiresAt = new \DateTimeImmutable('+1 hour');

                $user->setResetToken($token);
                $user->setResetTokenExpiresAt($expiresAt);
                $em->flush();

                $resetUrl = $this->generateUrl('app.reset_password', [
                    '_locale' => $request->getLocale(),
                    'token'   => $token,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $mail = (new Email())
                    ->from(new Address($_ENV['MAILER_FROM_ADDRESS'], 'Oasis Karurio'))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html(
                        '<p>Bonjour,</p>' .
                        '<p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe. Ce lien expire dans 1 heure.</p>' .
                        '<p><a href="' . $resetUrl . '">' . $resetUrl . '</a></p>' .
                        '<p>Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.</p>'
                    );

                $mailer->send($mail);
            }

            $this->addFlash('success', 'reset.flash.email_sent');
            return $this->redirectToRoute('app.forgot_password', ['_locale' => $request->getLocale()]);
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
            'recaptcha_key' => $_ENV['RECAPTCHA3_KEY'],
        ]);
    }

    #[Route('/{_locale}/reset-password/{token}', name: 'app.reset_password', requirements: ['_locale' => 'fr|en'])]
    public function reset(string $token, Request $request, UserRepository $userRepository, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        // Token invalide ou expiré
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'reset.flash.token_invalid');
            return $this->redirectToRoute('app.forgot_password', ['_locale' => $request->getLocale()]);
        }

        $form = $this->createForm(NewPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();

            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $em->flush();

            $this->addFlash('success', 'reset.flash.success');
            return $this->redirectToRoute('app.login', ['_locale' => $request->getLocale()]);
        }

        return $this->render('security/reset_password.html.twig', [
            'form'  => $form->createView(),
            'token' => $token,
            'recaptcha_key' => $_ENV['RECAPTCHA3_KEY'],
        ]);
    }
}