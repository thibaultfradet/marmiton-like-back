<?php

namespace App\Controller\Api;

use App\Repository\RecipeRepository;
use App\Service\ApiResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ApiSearchController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly RecipeRepository $recipeRepository,
    ) {
    }

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->api->success([]);
        }

        $recipes = $this->recipeRepository->search($query);

        $results = array_map(fn ($recipe) => [
            'id'          => $recipe->getId(),
            'label'       => $recipe->getLabel(),
            'description' => $recipe->getDescription(),
            'category'    => $recipe->getCategory()?->getLabel(),
            'author'      => $recipe->getAuthor()
                ? $recipe->getAuthor()->getFirstName() . ' ' . $recipe->getAuthor()->getLastName()
                : null,
        ], $recipes);

        return $this->api->success($results);
    }
}
