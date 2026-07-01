<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WishSearch\WishSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/wish-search', name: 'dashboard_wish_search_')]
class WishSearchController extends AbstractController
{
    public function __construct(
        private readonly WishSearchService $wishSearchService,
    ) {
    }

    #[Route('/topics', name: 'topics', methods: ['GET'])]
    public function topics(): JsonResponse
    {
        return $this->json([
            'topics' => $this->wishSearchService->topics(),
            'config' => $this->wishSearchService->config(),
        ]);
    }

    #[Route('', name: 'search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }

        $topic = trim((string) ($data['topic'] ?? ''));
        if ($topic === '') {
            return $this->json(['error' => 'Missing required field: topic'], Response::HTTP_BAD_REQUEST);
        }

        $criteria = is_array($data['criteria'] ?? null) ? $data['criteria'] : [];
        $limit = isset($data['limit']) && is_numeric($data['limit']) ? (int) $data['limit'] : null;

        try {
            $result = $this->wishSearchService->search($topic, $criteria, $limit);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return $this->json(['error' => 'Wish search failed. Please try again.'], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json($result);
    }
}
