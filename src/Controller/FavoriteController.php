<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavoriteController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorites')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('favorite/index.html.twig', [
            'recipes' => $user->getFavorites(),
        ]);
    }

    #[Route('/api/recipe/{id}/favorite', name: 'app_api_recipe_favorite', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Recipe $recipe, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isFavorite($recipe)) {
            $user->removeFavorite($recipe);
            $isFavorite = false;
        } else {
            $user->addFavorite($recipe);
            $isFavorite = true;
        }

        $entityManager->flush();

        return $this->json(['favorited' => $isFavorite]);
    }
}
