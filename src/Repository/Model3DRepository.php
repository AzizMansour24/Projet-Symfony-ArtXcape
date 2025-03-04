<?php

namespace App\Repository;

use App\Entity\Model3D;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Model3D>
 *
 * @method Model3D|null find($id, $lockMode = null, $lockVersion = null)
 * @method Model3D|null findOneBy(array $criteria, array $orderBy = null)
 * @method Model3D[]    findAll()
 * @method Model3D[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Model3DRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Model3D::class);
    }

    public function save(Model3D $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Model3D $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 