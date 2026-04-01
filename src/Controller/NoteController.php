<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Note;
use App\Service\NoteService;
use MyDashboard\Shared\Security\JwtUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/notes', name: 'dashboard_notes_')]
class NoteController extends AbstractController
{
    public function __construct(
        private readonly NoteService $noteService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $items = $this->noteService->findAllByOwner($this->getOwnerId());
        return $this->json(array_map($this->noteService->serialize(...), $items));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $item = $this->noteService->create($data, $this->getOwnerId());
        return $this->json($this->noteService->serialize($item), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Note $item): JsonResponse
    {
        $this->noteService->assertAccessible($item, $this->getOwnerId());
        $data = json_decode($request->getContent(), true) ?? [];
        $item = $this->noteService->update($item, $data, $this->getOwnerId());
        return $this->json($this->noteService->serialize($item));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Note $item): JsonResponse
    {
        $this->noteService->assertOwner($item, $this->getOwnerId());
        $this->noteService->delete($item);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/share', name: 'share', methods: ['POST'])]
    public function share(Request $request, Note $item): JsonResponse
    {
        $this->noteService->assertOwner($item, $this->getOwnerId());
        $data = json_decode($request->getContent(), true) ?? [];
        $userId = trim((string) ($data['userId'] ?? ''));

        if ($userId === '') {
            return $this->json(['error' => 'Missing required field: userId'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updated = $this->noteService->shareWithUser($item, $userId, $this->getOwnerId());
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->noteService->serialize($updated));
    }

    #[Route('/{id}/share/{userId}', name: 'unshare', methods: ['DELETE'])]
    public function unshare(Note $item, string $userId): JsonResponse
    {
        $this->noteService->assertOwner($item, $this->getOwnerId());

        $updated = $this->noteService->unshareWithUser($item, $userId, $this->getOwnerId());

        return $this->json($this->noteService->serialize($updated));
    }

    private function getOwnerId(): string
    {
        /** @var JwtUser $user */
        $user = $this->getUser();
        return $user->getUserId();
    }
}
