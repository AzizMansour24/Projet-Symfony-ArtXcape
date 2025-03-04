<?php

namespace App\Controller;

use App\Entity\CandidatureOffre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends AbstractController
{
    private $entityManager;
  
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function unreadCount(): Response
    {
        if (!$this->getUser()) {
            return new Response('0');
        }

        $count = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->count([
                'candidat' => $this->getUser(),
                'isRead' => false,
                'statut' => ['Accepté', 'Rejeté']
            ]);

        return new Response($count);
    }
} 