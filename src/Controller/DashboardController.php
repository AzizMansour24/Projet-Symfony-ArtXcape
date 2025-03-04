<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\UserRepository;

class DashboardController extends AbstractController    
{
    #[Route('/choose-dashboard', name: 'choose_dashboard')]
    public function chooseDashboard(): Response
    {
        // Vérifie si l'utilisateur est bien authentifié
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login'); // Redirige vers la connexion
        }

        $user = $this->getUser();

        // Vérifie si l'utilisateur a le rôle ADMIN
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('registration/choose_dashboard.html.twig', [
                'user' => $user,
            ]);
        }

        // Si l'utilisateur est un simple USER, il va directement vers le front-office
        return $this->redirectToRoute('front_office_home');
    }

    #[Route('/homedash', name: 'front_office_home')]
    public function frontOffice(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login'); // Sécurise l'accès si pas connecté
        }
        return $this->render('base.html.twig'); // Affiche le Front-Office
    }

    #[Route('/back', name: 'back_office_home')]
    #[IsGranted('ROLE_ADMIN')] // Sécurise la route pour les administrateurs uniquement
    public function backOffice(): Response
    {
        return $this->render('base2.html.twig'); // Affiche le Back-Office
    }

    #[Route('/back', name: 'app_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $totalUsers = count($users);
        $totalAdmins = 0;
        $totalNormalUsers = 0;

        foreach ($users as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $totalAdmins++;
            } elseif (in_array('ROLE_USER', $user->getRoles())) {
                $totalNormalUsers++;
            }
        }

        return $this->render('base2.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalNormalUsers' => $totalNormalUsers,
        ]);
    }
}
