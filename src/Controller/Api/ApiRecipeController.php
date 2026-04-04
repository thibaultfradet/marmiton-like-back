<?php

namespace App\Controller\Api;

use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use App\Service\ApiResponseService;
use App\Service\RecipeNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
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
        $page       = max(1, (int) $request->query->get('page', '1'));
        $limit      = min(50, max(1, (int) $request->query->get('limit', '15')));

        [$recipes, $total] = $this->buildFilteredQuery($categoryId, $tagId, $search, $page, $limit);

        return $this->api->success([
            'recipes'    => $this->normalizer->normalizeList($recipes, $user),
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'hasMore'    => ($page * $limit) < $total,
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

    #[Route('', name: 'api_recipes_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $error = $this->validateRecipePayload($data);
        if ($error) {
            return $this->api->error($error);
        }

        $recipe = new Recipe();
        $this->hydrateRecipe($recipe, $data);
        $now = new \DateTimeImmutable();
        $recipe->setCreatedAt($now)->setUpdatedAt($now)->setAuthor($user);

        $this->em->persist($recipe);
        $this->em->flush();

        return $this->api->success(
            $this->normalizer->normalize($recipe, $user),
            'Recette créée avec succès.',
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_recipes_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $recipe = $this->recipeRepository->find($id);

        if (!$recipe) {
            return $this->api->error('Recette introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($recipe->getAuthor() !== $user) {
            return $this->api->error('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $error = $this->validateRecipePayload($data);
        if ($error) {
            return $this->api->error($error);
        }

        $this->hydrateRecipe($recipe, $data);
        $recipe->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->api->success($this->normalizer->normalize($recipe, $user), 'Recette mise à jour.');
    }

    #[Route('/{id}/photo', name: 'api_recipes_photo_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadPhoto(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $recipe = $this->recipeRepository->find($id);

        if (!$recipe) {
            return $this->api->error('Recette introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($recipe->getAuthor() !== $user) {
            return $this->api->error('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        /** @var UploadedFile|null $photo */
        $photo = $request->files->get('photo');
        if (!$photo) {
            return $this->api->error('Aucun fichier fourni.');
        }

        $uploadsDir = $this->projectDir . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $photo->move($uploadsDir, 'recipe-' . $recipe->getId() . '.png');

        return $this->api->success(['photoUrl' => '/uploads/recipe-' . $recipe->getId() . '.png']);
    }

    #[Route('/{id}/photo', name: 'api_recipes_photo_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deletePhoto(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $recipe = $this->recipeRepository->find($id);

        if (!$recipe) {
            return $this->api->error('Recette introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($recipe->getAuthor() !== $user) {
            return $this->api->error('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        $photoPath = $this->projectDir . '/public/uploads/recipe-' . $recipe->getId() . '.png';
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }

        return $this->api->success(null, 'Photo supprimée.');
    }

    private function validateRecipePayload(array $data): ?string
    {
        if (empty(trim((string) ($data['label'] ?? '')))) {
            return 'Le titre est requis.';
        }
        if (empty(trim((string) ($data['ingredients'] ?? '')))) {
            return 'Les ingrédients sont requis.';
        }
        if (empty(trim((string) ($data['instructions'] ?? '')))) {
            return 'Les instructions sont requises.';
        }
        return null;
    }

    private function hydrateRecipe(Recipe $recipe, array $data): void
    {
        $recipe->setLabel(trim((string) $data['label']));
        $recipe->setDescription(isset($data['description']) ? trim((string) $data['description']) : null);
        $recipe->setIngredients(trim((string) $data['ingredients']));
        $recipe->setInstructions(trim((string) $data['instructions']));
        $recipe->setPreparationTime(isset($data['preparationTime']) ? (int) $data['preparationTime'] : null);
        $recipe->setCookingTime(isset($data['cookingTime']) ? (int) $data['cookingTime'] : null);
        $recipe->setQuantity(isset($data['quantity']) ? (int) $data['quantity'] : null);

        $category = isset($data['categoryId']) ? $this->categoryRepository->find((int) $data['categoryId']) : null;
        $recipe->setCategory($category);

        // Sync tags
        foreach ($recipe->getTags()->toArray() as $tag) {
            $recipe->removeTag($tag);
        }
        foreach ((array) ($data['tagIds'] ?? []) as $tagId) {
            $tag = $this->tagRepository->find((int) $tagId);
            if ($tag) {
                $recipe->addTag($tag);
            }
        }
    }

    /**
     * @return array{0: \App\Entity\Recipe[], 1: int}
     */
    private function buildFilteredQuery(?string $categoryId, ?string $tagId, ?string $search, int $page, int $limit): array
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

        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: true);
        $total     = count($paginator);
        $recipes   = iterator_to_array($paginator->getIterator());

        return [$recipes, $total];
    }
}
