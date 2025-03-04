<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AvatarExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_avatar_viewer_url', [$this, 'getAvatarViewerUrl']),
        ];
    }

    public function getAvatarViewerUrl(?string $glbUrl): string
    {
        if (!$glbUrl) {
            return '';
        } 

        // Extraire l'ID de l'URL .glb
        // Format URL: https://models.readyplayer.me/[ID].glb
        $id = basename($glbUrl, '.glb');
        
        // Retourner l'URL de l'image de l'avatar
        return "https://api.readyplayer.me/v1/avatars/$id/snapshot.jpg";
    }
} 