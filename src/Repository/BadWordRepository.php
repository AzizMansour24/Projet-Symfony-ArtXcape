<?php

namespace App\Repository;

use App\Entity\BadWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BadWord>
 *
 * @method BadWord|null find($id, $lockMode = null, $lockVersion = null)
 * @method BadWord|null findOneBy(array $criteria, array $orderBy = null)
 * @method BadWord[]    findAll()
 * @method BadWord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BadWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BadWord::class);
    }

    public function save(BadWord $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BadWord $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 