<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function findByUserAndRecipe(User $user, Recipe $recipe): ?Rating
    {
        return $this->findOneBy(['user' => $user, 'recipe' => $recipe]);
    }

    /**
     * @return array{average: float|null, count: int}
     */
    public function getAverageForRecipe(Recipe $recipe): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.value) as average, COUNT(r.id) as count')
            ->where('r.recipe = :recipe')
            ->setParameter('recipe', $recipe)
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => $result['average'] ? round((float) $result['average'], 1) : null,
            'count' => (int) $result['count'],
        ];
    }

    /**
     * Returns a map of recipeId => ['average' => float, 'count' => int] for all rated recipes.
     */
    public function getAveragesForAllRecipes(): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.recipe) as recipeId, AVG(r.value) as average, COUNT(r.id) as count')
            ->groupBy('r.recipe')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $row) {
            $map[$row['recipeId']] = [
                'average' => round((float) $row['average'], 1),
                'count' => (int) $row['count'],
            ];
        }

        return $map;
    }
}
