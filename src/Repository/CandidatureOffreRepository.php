<?php

namespace App\Repository;

use App\Entity\CandidatureOffre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CandidatureOffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidatureOffre::class);
    }

    /**
     * Fetch all CandidatureOffre entities with their related Candidature and Emploi.
     *
     * @return CandidatureOffre[]
     */
    public function findAllWithCandidatureAndEmploi(): array
    {
        return $this->createQueryBuilder('co')
            ->leftJoin('co.candidature', 'candidature') // Join Candidature
            ->addSelect('candidature')
            ->leftJoin('candidature.candidat', 'candidat') // Join Candidat through Candidature
            ->addSelect('candidat')
            ->leftJoin('co.emploi', 'emploi') // Join Emploi
            ->addSelect('emploi')
            ->getQuery()
            ->getResult();
    }
}