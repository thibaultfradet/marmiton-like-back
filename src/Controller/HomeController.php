<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        CategoryRepository $categoryRepository,
        RatingRepository $ratingRepository,
        RecipeRepository $recipeRepository,
        TagRepository $tagRepository,
        UserRepository $userRepository,
    ): Response {
        $user = $this->getUser();
        $favoriteIds = [];
        if ($user instanceof User) {
            $favoriteIds = $user->getFavorites()->map(fn($r) => $r->getId())->toArray();
        }

        return $this->render('home/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'recipes' => $recipeRepository->findBy([], ['createdAt' => 'DESC']),
            'tags' => $tagRepository->findAll(),
            'users' => $userRepository->findAll(),
            'favoriteIds' => $favoriteIds,
            'ratingsMap' => $ratingRepository->getAveragesForAllRecipes(),
        ]);
    }
}
