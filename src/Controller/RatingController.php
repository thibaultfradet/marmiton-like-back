<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RatingController extends AbstractController
{
    #[Route('/api/recipe/{id}/rate', name: 'app_api_recipe_rate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rate(
        Recipe $recipe,
        Request $request,
        RatingRepository $ratingRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $value = (int) $request->getPayload()->get('value');
        if ($value < 1 || $value > 5) {
            return $this->json(['error' => 'La note doit être entre 1 et 5'], 400);
        }

        $rating = $ratingRepository->findByUserAndRecipe($user, $recipe);

        if ($rating) {
            $rating->setValue($value);
        } else {
            $rating = new Rating();
            $rating->setUser($user);
            $rating->setRecipe($recipe);
            $rating->setValue($value);
            $rating->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($rating);
        }

        $entityManager->flush();

        $stats = $ratingRepository->getAverageForRecipe($recipe);

        return $this->json([
            'userRating' => $value,
            'average' => $stats['average'],
            'count' => $stats['count'],
        ]);
    }
}
