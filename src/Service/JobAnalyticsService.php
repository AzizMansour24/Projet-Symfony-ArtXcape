<?php

namespace App\Service;

use App\Entity\Emploi;
use App\Entity\CandidatureOffre;
use Doctrine\ORM\EntityManagerInterface;

class JobAnalyticsService
{  
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getJobStats(Emploi $emploi): array
    {
        $now = new \DateTime();
        $twoHoursAgo = (new \DateTime())->modify('-2 hours');
        $twentyFourHoursAgo = (new \DateTime())->modify('-24 hours');

        $qb = $this->entityManager->createQueryBuilder();
        
        // Candidatures des dernières 2 heures
        $recentApplications = $qb->select('COUNT(c.id)')
            ->from(CandidatureOffre::class, 'c')
            ->where('c.emploi = :emploi')
            ->andWhere('c.dateCandidature >= :twoHoursAgo')
            ->setParameter('emploi', $emploi)
            ->setParameter('twoHoursAgo', $twoHoursAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Candidatures des dernières 24 heures
        $qb = $this->entityManager->createQueryBuilder();
        $dailyApplications = $qb->select('COUNT(c.id)')
            ->from(CandidatureOffre::class, 'c')
            ->where('c.emploi = :emploi')
            ->andWhere('c.dateCandidature >= :twentyFourHoursAgo')
            ->setParameter('emploi', $emploi)
            ->setParameter('twentyFourHoursAgo', $twentyFourHoursAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Total des candidatures
        $qb = $this->entityManager->createQueryBuilder();
        $totalApplications = $qb->select('COUNT(c.id)')
            ->from(CandidatureOffre::class, 'c')
            ->where('c.emploi = :emploi')
            ->setParameter('emploi', $emploi)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'recent' => $recentApplications,
            'daily' => $dailyApplications,
            'total' => $totalApplications,
            'lastUpdate' => $now->format('Y-m-d H:i:s')
        ];
    }
} 