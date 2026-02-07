<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    //    /**
    //     * @return Recipe[] Returns an array of Recipe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Recipe
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Recipe[] Returns an array of Recipe objects matching the search query
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.author', 'a')
            ->where('r.label LIKE :query OR r.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('CASE WHEN r.label LIKE :exactQuery THEN 0 ELSE 1 END', 'ASC')
            ->addOrderBy('r.label', 'ASC')
            ->setParameter('exactQuery', $query . '%')
            ->setMaxResults(8)
            ->select('r, c, a')
            ->getQuery()
            ->getResult()
        ;
    }
}
