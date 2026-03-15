<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ShoppingList;
use App\Entity\ShoppingListProduct;
use App\Repository\ShoppingListProductRepository;
use App\Repository\ShoppingListRepository;
use MyDashboard\Shared\Security\JwtUser;
use App\Service\ShoppingListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/dashboard/shopping-lists', name: 'dashboard_shopping_lists_')]
class ShoppingListController extends AbstractController
{
    public function __construct(
        private readonly ShoppingListService $shoppingListService,
        private readonly ShoppingListRepository $listRepository,
        private readonly ShoppingListProductRepository $productRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $lists = $this->shoppingListService->findAllByOwner($this->getOwnerId());

        return $this->json(array_map($this->shoppingListService->serializeList(...), $lists));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $list = $this->shoppingListService->create($data, $this->getOwnerId());
        } catch (ValidationFailedException $e) {
            return $this->json(['errors' => $this->formatValidationErrors($e)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->shoppingListService->serializeList($list), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ShoppingList $list): JsonResponse
    {
        $this->assertOwner($list);

        return $this->json($this->shoppingListService->serializeList($list));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, ShoppingList $list): JsonResponse
    {
        $this->assertOwner($list);
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $list = $this->shoppingListService->update($list, $data, $this->getOwnerId());
        } catch (ValidationFailedException $e) {
            return $this->json(['errors' => $this->formatValidationErrors($e)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->shoppingListService->serializeList($list));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(ShoppingList $list): JsonResponse
    {
        $this->assertOwner($list);
        $this->shoppingListService->delete($list);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(Request $request, ShoppingList $list): JsonResponse
    {
        $this->assertOwner($list);
        $data = json_decode($request->getContent(), true) ?? [];

        if (!isset($data['status'])) {
            return $this->json(['error' => 'Missing required field: status'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updated = $this->shoppingListService->updateStatus($list, (string) $data['status']);
        } catch (ValidationFailedException $e) {
            return $this->json(['errors' => $this->formatValidationErrors($e)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->shoppingListService->serializeList($updated));
    }

    #[Route('/{id}/products', name: 'add_product', methods: ['POST'])]
    public function addProduct(Request $request, ShoppingList $list): JsonResponse
    {
        $this->assertOwner($list);
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $product = $this->shoppingListService->addProduct($list, $data, $this->getOwnerId());
        } catch (ValidationFailedException $e) {
            return $this->json(['errors' => $this->formatValidationErrors($e)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->shoppingListService->serializeProduct($product), Response::HTTP_CREATED);
    }

    #[Route('/{listId}/products/{productId}', name: 'remove_product', methods: ['DELETE'])]
    public function removeProduct(string $listId, string $productId): JsonResponse
    {
        $list = $this->listRepository->find($listId);
        if (!$list) {
            return $this->json(['error' => 'Shopping list not found.'], Response::HTTP_NOT_FOUND);
        }
        $this->assertOwner($list);

        $product = $this->productRepository->find($productId);
        if (!$product || $product->getShoppingList() !== $list) {
            return $this->json(['error' => 'Product not found in this list.'], Response::HTTP_NOT_FOUND);
        }

        $this->shoppingListService->removeProduct($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getOwnerId(): string
    {
        /** @var JwtUser $user */
        $user = $this->getUser();
        return $user->getUserId();
    }

    private function assertOwner(ShoppingList $list): void
    {
        if ($list->getOwnerId() !== $this->getOwnerId()) {
            throw $this->createAccessDeniedException('You do not own this shopping list.');
        }
    }

    /** @return string[] */
    private function formatValidationErrors(ValidationFailedException $e): array
    {
        $messages = [];
        foreach ($e->getViolations() as $v) {
            $messages[] = $v->getPropertyPath() . ': ' . $v->getMessage();
        }
        return $messages;
    }
}
