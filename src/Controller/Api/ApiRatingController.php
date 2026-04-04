<?php

namespace App\Controller\Api;

use App\Entity\Rating;
use App\Entity\User;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiRatingController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly RecipeRepository $recipeRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/recipes/{id}/rate', name: 'api_recipes_rate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rate(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $recipe = $this->recipeRepository->find($id);

        if (!$recipe) {
            return $this->api->error('Recette introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $value = isset($data['value']) ? (int) $data['value'] : 0;

        if ($value < 1 || $value > 5) {
            return $this->api->error('La note doit être entre 1 et 5.');
        }

        $rating = $this->ratingRepository->findByUserAndRecipe($user, $recipe);

        if ($rating) {
            $rating->setValue($value);
        } else {
            $rating = new Rating();
            $rating->setUser($user)->setRecipe($recipe)->setValue($value)->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($rating);
        }

        $this->em->flush();

        $stats = $this->ratingRepository->getAverageForRecipe($recipe);

        return $this->api->success([
            'userRating' => $value,
            'average'    => $stats['average'],
            'count'      => $stats['count'],
        ]);
    }
}
