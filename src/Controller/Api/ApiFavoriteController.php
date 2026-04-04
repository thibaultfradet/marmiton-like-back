<?php

namespace App\Controller\Api;

use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\RecipeRepository;
use App\Service\ApiResponseService;
use App\Service\RecipeNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiFavoriteController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly RecipeNormalizer $normalizer,
        private readonly RecipeRepository $recipeRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/favorites', name: 'api_favorites_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser();
        $recipes = $user->getFavorites()->toArray();

        return $this->api->success([
            'recipes' => $this->normalizer->normalizeList($recipes, $user),
        ]);
    }

    #[Route('/api/recipes/{id}/favorite', name: 'api_recipes_favorite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $recipe = $this->recipeRepository->find($id);

        if (!$recipe) {
            return $this->api->error('Recette introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($user->isFavorite($recipe)) {
            $user->removeFavorite($recipe);
            $favorited = false;
        } else {
            $user->addFavorite($recipe);
            $favorited = true;
        }

        $this->em->flush();

        return $this->api->success(['favorited' => $favorited]);
    }
}
