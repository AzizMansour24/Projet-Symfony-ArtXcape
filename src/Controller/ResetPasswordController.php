<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordController extends AbstractController 
{
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request, 
        MailerInterface $mailer, 
        EntityManagerInterface $entityManager, 
        TokenGeneratorInterface $tokenGenerator,
        LoggerInterface $logger
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            dump('Email reçu:', $email); // Debug

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Format d\'email invalide');
                return $this->render('reset_password/request.html.twig');
            }

            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            dump('Utilisateur trouvé:', $user); // Debug

            if (!$user) {
                $this->addFlash('error', 'Cette adresse email n\'existe pas.');
                return $this->render('reset_password/request.html.twig');
            }

            dump('Avant génération du token');

            try {
                $token = bin2hex(random_bytes(32));
                dump('Token généré:', $token); // Debug

                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

                $entityManager->persist($user);
                $entityManager->flush();

                dump('Token sauvegardé:', $user->getResetToken()); // Debug

                $email = (new TemplatedEmail())
                    ->from(new Address('souhaila.karkoub@esprit.tn', 'ArtXcape'))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('reset_password/email.html.twig')
                    ->context([
                        'resetToken' => $token
                    ]);

                dump('Email préparé:', $email); // Debug

                $mailer->send($email);

                dump('Email envoyé avec succès'); // Debug

                $this->addFlash('success', 'Un email a été envoyé pour réinitialiser votre mot de passe.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                dump('Erreur:', $e->getMessage()); // Debug
                $logger->error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email.');
            }
        }

        return $this->render('reset_password/request.html.twig');
    }

    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Token invalide ou expiré');
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
