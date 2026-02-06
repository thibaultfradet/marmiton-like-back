<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
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
        RecipeRepository $recipeRepository,
        TagRepository $tagRepository,
        UserRepository $userRepository,
    ): Response {
        return $this->render('home/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'recipes' => $recipeRepository->findBy([], ['createdAt' => 'DESC']),
            'tags' => $tagRepository->findAll(),
            'users' => $userRepository->findAll(),
        ]);
    }
}
