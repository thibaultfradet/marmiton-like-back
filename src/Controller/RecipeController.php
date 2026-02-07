<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipe')]
final class RecipeController extends AbstractController
{
    #[Route('/my', name: 'app_recipe_my')]
    public function myRecipes(RecipeRepository $recipeRepository): Response
    {
        return $this->render('recipe/my.html.twig', [
            'recipes' => $recipeRepository->findBy(
                ['author' => $this->getUser()],
                ['createdAt' => 'DESC']
            ),
        ]);
    }

    #[Route('/new', name: 'app_recipe_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): Response {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();
            $recipe->setCreatedAt($now);
            $recipe->setUpdatedAt($now);
            $recipe->setAuthor($this->getUser());

            $entityManager->persist($recipe);
            $entityManager->flush();

            // Handle photo upload (after flush so we have the ID)
            /** @var UploadedFile|null $photo */
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $uploadsDir = $projectDir . '/public/uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                $photo->move($uploadsDir, 'recipe-' . $recipe->getId() . '.png');
            }

            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
        }

        return $this->render('recipe/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recipe_show', requirements: ['id' => '\d+'])]
    public function show(Recipe $recipe, RatingRepository $ratingRepository): Response
    {
        $user = $this->getUser();
        $isFavorite = ($user instanceof User) && $user->isFavorite($recipe);

        $ratingStats = $ratingRepository->getAverageForRecipe($recipe);
        $userRating = null;
        if ($user instanceof User) {
            $existing = $ratingRepository->findByUserAndRecipe($user, $recipe);
            $userRating = $existing?->getValue();
        }

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'isFavorite' => $isFavorite,
            'ratingAverage' => $ratingStats['average'],
            'ratingCount' => $ratingStats['count'],
            'userRating' => $userRating,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recipe_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): Response {
        // Only the author can edit their recipe
        if ($recipe->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres recettes.');
        }

        $form = $this->createForm(RecipeType::class, $recipe, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setUpdatedAt(new \DateTimeImmutable());

            $uploadsDir = $projectDir . '/public/uploads';
            $photoPath = $uploadsDir . '/recipe-' . $recipe->getId() . '.png';

            // Handle photo deletion
            if ($form->get('deletePhoto')->getData() && file_exists($photoPath)) {
                unlink($photoPath);
            }

            // Handle new photo upload (overwrite if exists)
            /** @var UploadedFile|null $photo */
            $photo = $form->get('photo')->getData();
            if ($photo) {
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                $photo->move($uploadsDir, 'recipe-' . $recipe->getId() . '.png');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
        }

        return $this->render('recipe/edit.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
        ]);
    }
}
