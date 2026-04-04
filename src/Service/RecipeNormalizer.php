<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\RatingRepository;

class RecipeNormalizer
{
    public function __construct(private readonly RatingRepository $ratingRepository)
    {
    }

    /**
     * Normalize a single Recipe into a canonical array for API responses.
     *
     * @param array<int, array{average: float|null, count: int}>|null $ratingsMap pre-fetched ratings map (recipeId => data)
     */
    public function normalize(Recipe $recipe, ?User $currentUser = null, ?array $ratingsMap = null): array
    {
        $id = $recipe->getId();

        if ($ratingsMap !== null) {
            $ratingData = $ratingsMap[$id] ?? ['average' => null, 'count' => 0];
        } else {
            $ratingData = $this->ratingRepository->getAverageForRecipe($recipe);
        }

        $photoPath = __DIR__ . '/../../public/uploads/recipe-' . $id . '.png';
        $photoUrl  = file_exists($photoPath) ? '/uploads/recipe-' . $id . '.png' : null;

        $category = $recipe->getCategory();
        $author   = $recipe->getAuthor();

        return [
            'id'              => $id,
            'label'           => $recipe->getLabel(),
            'description'     => $recipe->getDescription(),
            'ingredients'     => $recipe->getIngredients(),
            'instructions'    => $recipe->getInstructions(),
            'preparationTime' => $recipe->getPreparationTime(),
            'cookingTime'     => $recipe->getCookingTime(),
            'quantity'        => $recipe->getQuantity(),
            'createdAt'       => $recipe->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt'       => $recipe->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'photoUrl'        => $photoUrl,
            'category'        => $category ? ['id' => $category->getId(), 'label' => $category->getLabel()] : null,
            'tags'            => $recipe->getTags()->map(
                fn ($tag) => ['id' => $tag->getId(), 'label' => $tag->getLabel()]
            )->toArray(),
            'author'          => $author ? [
                'id'        => $author->getId(),
                'firstName' => $author->getFirstName(),
                'lastName'  => $author->getLastName(),
            ] : null,
            'ratingAverage' => $ratingData['average'],
            'ratingCount'   => $ratingData['count'],
            'isFavorite'    => $currentUser ? $currentUser->isFavorite($recipe) : false,
        ];
    }

    /**
     * Normalize multiple recipes efficiently using a pre-fetched ratings map.
     *
     * @param Recipe[] $recipes
     * @return array<int, array<string, mixed>>
     */
    public function normalizeList(array $recipes, ?User $currentUser = null): array
    {
        $ratingsMap = $this->ratingRepository->getAveragesForAllRecipes();

        return array_values(array_map(
            fn (Recipe $r) => $this->normalize($r, $currentUser, $ratingsMap),
            $recipes
        ));
    }
}
