<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArtLike;
use App\Entity\BadWord;
use App\Entity\Candidature;
use App\Entity\CandidatureOffre;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\Comment;
use App\Entity\Conversation;
use App\Entity\Donation;
use App\Entity\Emploi;
use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\Galerie;
use App\Entity\Like;
use App\Entity\Inscription;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\Model3D;
use App\Entity\Reaction;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('frontOffice/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/back2', name: 'app_back')]
    public function back(): Response
    {
        $articleCount = $this->entityManager->getRepository(Article::class)->count([]);
        $artLikeCount = $this->entityManager->getRepository(ArtLike::class)->count([]);
        $badWordCount = $this->entityManager->getRepository(BadWord::class)->count([]);
        $candidatureCount = $this->entityManager->getRepository(Candidature::class)->count([]);
        $candidatureOffreCount = $this->entityManager->getRepository(CandidatureOffre::class)->count([]);
        $categorieCount = $this->entityManager->getRepository(Categorie::class)->count([]);
        $commandeCount = $this->entityManager->getRepository(Commande::class)->count([]);
        $commentCount = $this->entityManager->getRepository(Comment::class)->count([]);
        $conversationCount = $this->entityManager->getRepository(Conversation::class)->count([]);
        $donationCount = $this->entityManager->getRepository(Donation::class)->count([]);
        $emploiCount = $this->entityManager->getRepository(Emploi::class)->count([]);
        $eventCount = $this->entityManager->getRepository(Event::class)->count([]);
        $formationCount = $this->entityManager->getRepository(Formation::class)->count([]);
        $galerieCount = $this->entityManager->getRepository(Galerie::class)->count([]);
        $likeCount = $this->entityManager->getRepository(Like::class)->count([]);
        $inscriptionCount = $this->entityManager->getRepository(Inscription::class)->count([]);
        $messageCount = $this->entityManager->getRepository(Message::class)->count([]);
        $postCount = $this->entityManager->getRepository(Post::class)->count([]);
        $model3DCount = $this->entityManager->getRepository(Model3D::class)->count([]);
        $reactionCount = $this->entityManager->getRepository(Reaction::class)->count([]);
        $reservationCount = $this->entityManager->getRepository(Reservation::class)->count([]);
        $userCount = $this->entityManager->getRepository(User::class)->count([]);

        return $this->render('backOffice/acceuil.html.twig', [
            'articleCount' => $articleCount,
            'artLikeCount' => $artLikeCount,
            'badWordCount' => $badWordCount,
            'candidatureCount' => $candidatureCount,
            'candidatureOffreCount' => $candidatureOffreCount,
            'categorieCount' => $categorieCount,
            'commandeCount' => $commandeCount,
            'commentCount' => $commentCount,
            'conversationCount' => $conversationCount,
            'donationCount' => $donationCount,
            'emploiCount' => $emploiCount,
            'eventCount' => $eventCount,
            'formationCount' => $formationCount,
            'galerieCount' => $galerieCount,
            'likeCount' => $likeCount,
            'inscriptionCount' => $inscriptionCount,
            'messageCount' => $messageCount,
            'postCount' => $postCount,
            'model3DCount' => $model3DCount,
            'reactionCount' => $reactionCount,
            'reservationCount' => $reservationCount,
            'userCount' => $userCount,
        ]);
    }
}
