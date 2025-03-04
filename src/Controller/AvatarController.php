<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User; 
use Symfony\Component\HttpFoundation\Request;

class AvatarController extends AbstractController
{
    #[Route('/avatar', name: 'avatar_create')]
    public function createAvatar(Request $request): Response
    {
        if ($request->query->has('url')) {
            // Stocke l'URL de l'avatar dans la session
            $request->getSession()->set('temp_avatar_url', $request->query->get('url'));
            
            // Retourne un script qui fermera la fenêtre
            return new Response(
                '<script>
                    window.opener.location.reload();
                    window.close();
                </script>'
            );
        }

        return $this->render('avatar.html.twig', [
            'standalone' => true
        ]);
    }

    #[Route('/avatar/view/{id}', name: 'avatar_view')]
    public function viewAvatar(User $user): Response
    {
        if (!$user->getAvatarUrl()) {
            throw $this->createNotFoundException('Aucun avatar trouvé pour cet utilisateur.');
        }

        return $this->render('avatar_viewer.html.twig', [
            'avatar_url' => $user->getAvatarUrl()
        ]);
    }
}
