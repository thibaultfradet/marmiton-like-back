<?php

namespace App\Controller;

use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/api/search', name: 'app_api_search')]
    public function search(Request $request, RecipeRepository $recipeRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        // Return empty array if query is empty or less than 2 characters
        if (empty($query) || strlen($query) < 2) {
            return $this->json([]);
        }

        $recipes = $recipeRepository->search($query);

        // Format results for JSON response
        $results = array_map(function ($recipe) {
            return [
                'id' => $recipe->getId(),
                'label' => $recipe->getLabel(),
                'description' => $recipe->getDescription() ?? '',
                'category' => $recipe->getCategory()?->getLabel() ?? null,
                'author' => $recipe->getAuthor()?->getFirstName() . ' ' . $recipe->getAuthor()?->getLastName(),
            ];
        }, $recipes);

        return $this->json($results);
    }
}
