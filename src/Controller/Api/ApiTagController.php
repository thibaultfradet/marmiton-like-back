<?php

namespace App\Controller\Api;

use App\Repository\TagRepository;
use App\Service\ApiResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tags')]
final class ApiTagController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly TagRepository $tagRepository,
    ) {
    }

    #[Route('', name: 'api_tags_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tags = $this->tagRepository->findBy([], ['label' => 'ASC']);

        return $this->api->success(array_map(
            fn ($t) => ['id' => $t->getId(), 'label' => $t->getLabel()],
            $tags
        ));
    }
}
