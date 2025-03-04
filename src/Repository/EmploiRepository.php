<?php

namespace App\Repository;

use App\Entity\Emploi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emploi>
 *
 * @method Emploi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Emploi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Emploi[]    findAll()
 * @method Emploi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmploiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emploi::class);
    }
    public function findByTitre(string $titre): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.titre LIKE :titre')
            ->setParameter('titre', '%' . $titre . '%')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(?float $budget, ?string $competence, ?string $lieu): array
{
    $qb = $this->createQueryBuilder('e');

    // Filter by budget
    if ($budget) {
        $qb->andWhere('e.budget <= :budget')
           ->setParameter('budget', $budget);
    }

    // Filter by competence
    if ($competence) {
        $qb->andWhere('e.competences_requises LIKE :competence')
           ->setParameter('competence', '%' . $competence . '%');
    }

    // Filter by lieu (location)
    if ($lieu) {
        $qb->andWhere('e.lieu LIKE :lieu')
           ->setParameter('lieu', '%' . $lieu . '%');
    }

    return $qb->getQuery()->getResult();
}


}