<?php

namespace App\Repository;

use App\Entity\ArtLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtLike>
 *
 * @method ArtLike|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArtLike|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArtLike[]    findAll()
 * @method ArtLike[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtLike::class);
    }

    public function save(ArtLike $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ArtLike $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 