<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use App\Service\ApiResponseService;
use App\Service\RecipeNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recipes')]
final class ApiRecipeController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly RecipeNormalizer $normalizer,
        private readonly RecipeRepository $recipeRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
    ) {
    }

    #[Route('', name: 'api_recipes_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $categoryId = $request->query->get('category');
        $tagId      = $request->query->get('tag');
        $search     = $request->query->get('q');

        $recipes = $this->buildFilteredQuery($categoryId, $tagId, $search);

        return $this->api->success([
            'recipes'    => $this->normalizer->normalizeList($recipes, $user),
            'categories' => array_map(
                fn ($c) => ['id' => $c->getId(), 'label' => $c->getLabel()],
                $this->categoryRepository->findBy([], ['label' => 'ASC'])
            ),
            'tags' => array_map(
                fn ($t) => ['id' => $t->getId(), 'label' => $t->getLabel()],
                $this->tagRepository->findBy([], ['label' => 'ASC'])
            ),
        ]);
    }

    #[Route('/my', name: 'api_recipes_my', methods: ['GET'])]
    public function my(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $recipes = $this->recipeRepository->findBy(['author' => $user], ['createdAt' => 'DESC']);

        return $this->api->success([
            'recipes' => $this->normalizer->normalizeList($recipes, $user),
        ]);
    }

    #[Route('/{id}', name: 'api_recipes_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        /** @var User|null $user */
        $user   = $this->getUser();
        $recipe = $this->recipeRepository->find($id);

        if (!$recipe) {
            return $this->api->error('Recette introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->normalizer->normalize($recipe, $user);

        if ($user) {
            $userRating          = $this->ratingRepository->findByUserAndRecipe($user, $recipe);
            $data['userRating']  = $userRating?->getValue();
        }

        return $this->api->success($data);
    }

    /**
     * @return \App\Entity\Recipe[]
     */
    private function buildFilteredQuery(?string $categoryId, ?string $tagId, ?string $search): array
    {
        $qb = $this->recipeRepository->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->leftJoin('r.Tags', 't')
            ->leftJoin('r.author', 'a')
            ->orderBy('r.createdAt', 'DESC');

        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')->setParameter('categoryId', $categoryId);
        }

        if ($tagId) {
            $qb->andWhere('t.id = :tagId')->setParameter('tagId', $tagId);
        }

        if ($search) {
            $qb->andWhere('r.label LIKE :search OR r.description LIKE :search OR r.ingredients LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
