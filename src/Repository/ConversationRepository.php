<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findOrCreateConversation(User $user1, User $user2): Conversation
    {
        $entityManager = $this->getEntityManager();
        
        $dql = "SELECT c FROM App\Entity\Conversation c
                JOIN c.participants p
                WHERE p = :user1 OR p = :user2
                GROUP BY c
                HAVING COUNT(p) = 2";

        $query = $entityManager->createQuery($dql)
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2);

        try {
            $conversation = $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // Create new conversation if none exists
            $conversation = new Conversation();
            $conversation->addParticipant($user1);
            $conversation->addParticipant($user2);
            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        return $conversation;
    }
} 