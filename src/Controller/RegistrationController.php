<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;


class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'URL de l'avatar depuis la session
            $avatarUrl = $request->getSession()->get('avatar_url');
            if ($avatarUrl) {
                $user->setAvatarUrl($avatarUrl);
                // Nettoyer la session
                $request->getSession()->remove('avatar_url');
                $request->getSession()->remove('avatar_success');
            }

            // Encoder le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Définir les rôles
            $roles = $form->get('roles')->getData();
            $user->setRoles($roles);

            $entityManager->persist($user);
            $entityManager->flush();

            // Connecter automatiquement l'utilisateur
            $security->login($user, 'form_login', 'main');

            // Redirection en fonction du rôle
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('choose_dashboard');
            } else {
                return $this->redirectToRoute('front_office_home');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'avatar_success' => $request->getSession()->get('avatar_success', false),
            'avatar_url' => $request->getSession()->get('avatar_url')
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register'); 
    }

    #[Route('/user/update-avatar3d', name: 'user_update_avatar3d', methods: ['POST'])]
    public function updateAvatar3D(Request $request, Session $session): JsonResponse
    {
        $avatarUrl = $request->request->get('avatar_url');

        if (!$avatarUrl) {
            return new JsonResponse([
                'success' => false,
                'message' => 'URL de l\'avatar manquante'
            ], 400);
        }

        $session->set('avatar_url', $avatarUrl);
        $session->set('avatar_success', true);

        return new JsonResponse([
            'success' => true,
            'avatar_url' => $avatarUrl
        ]);
    }


}
