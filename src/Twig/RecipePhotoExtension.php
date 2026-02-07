<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RecipePhotoExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('recipe_photo_url', $this->getRecipePhotoUrl(...)),
        ];
    }

    /**
     * Returns the URL path to the recipe photo, or null if no photo exists.
     */
    public function getRecipePhotoUrl(int $recipeId): ?string
    {
        $path = $this->projectDir . '/public/uploads/recipe-' . $recipeId . '.png';
        if (file_exists($path)) {
            return '/uploads/recipe-' . $recipeId . '.png';
        }
        return null;
    }
}
