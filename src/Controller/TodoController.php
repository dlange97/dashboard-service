<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TodoItem;
use MyDashboard\Shared\Security\JwtUser;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/todos', name: 'dashboard_todos_')]
class TodoController extends AbstractController
{
    public function __construct(
        private readonly TodoService $todoService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $items = $this->todoService->findAllByOwner($this->getOwnerId());
        return $this->json(array_map($this->todoService->serialize(...), $items));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $item = $this->todoService->create($data, $this->getOwnerId());
        return $this->json($this->todoService->serialize($item), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, TodoItem $item): JsonResponse
    {
        $this->assertAccessible($item);
        $data = json_decode($request->getContent(), true) ?? [];
        $item = $this->todoService->update($item, $data, $this->getOwnerId());
        return $this->json($this->todoService->serialize($item));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(TodoItem $item): JsonResponse
    {
        $this->assertOwner($item);
        $this->todoService->delete($item);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['PATCH'])]
    public function toggle(TodoItem $item): JsonResponse
    {
        $this->assertAccessible($item);
        $item = $this->todoService->toggle($item, $this->getOwnerId());
        return $this->json($this->todoService->serialize($item));
    }

    #[Route('/{id}/share', name: 'share', methods: ['POST'])]
    public function share(Request $request, TodoItem $item): JsonResponse
    {
        $this->assertOwner($item);
        $data = json_decode($request->getContent(), true) ?? [];
        $userId = trim((string) ($data['userId'] ?? ''));

        if ($userId === '') {
            return $this->json(['error' => 'Missing required field: userId'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updated = $this->todoService->shareWithUser($item, $userId, $this->getOwnerId());
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->todoService->serialize($updated));
    }

    #[Route('/{id}/share/{userId}', name: 'unshare', methods: ['DELETE'])]
    public function unshare(TodoItem $item, string $userId): JsonResponse
    {
        $this->assertOwner($item);

        $updated = $this->todoService->unshareWithUser($item, $userId, $this->getOwnerId());

        return $this->json($this->todoService->serialize($updated));
    }

    private function getOwnerId(): string
    {
        /** @var JwtUser $user */
        $user = $this->getUser();
        return $user->getUserId();
    }

    private function assertOwner(TodoItem $item): void
    {
        if ($item->getOwnerId() !== $this->getOwnerId()) {
            throw $this->createAccessDeniedException('You do not own this todo item.');
        }
    }

    private function assertAccessible(TodoItem $item): void
    {
        $userId = $this->getOwnerId();
        if ($item->getOwnerId() === $userId) {
            return;
        }

        if (in_array($userId, $item->getSharedWithUserIds(), true)) {
            return;
        }

        throw $this->createAccessDeniedException('You do not have access to this todo item.');
    }

}

