<?php

namespace App\Controller;

use App\Entity\Inscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InscriptionController extends AbstractController
{
    #[Route('/backinscription', name: 'back_showinscription')]
    public function show(EntityManagerInterface $entityManager): Response
    {
        $inscriptions = $entityManager->getRepository(Inscription::class)->findAll();
        
        return $this->render('backOffice/formation/inscription.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }
}
