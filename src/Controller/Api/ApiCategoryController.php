<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use App\Service\ApiResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
final class ApiCategoryController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->categoryRepository->findBy([], ['label' => 'ASC']);

        return $this->api->success(array_map(
            fn ($c) => ['id' => $c->getId(), 'label' => $c->getLabel()],
            $categories
        ));
    }
}
