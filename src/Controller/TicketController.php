<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Enums\UserRole;
use App\Repository\TicketRepository;
use JsonException;

class TicketController extends BaseController
{
    private TicketRepository $repository;

    public function __construct()
    {
        $this->repository = new TicketRepository();
    }

    /**
     * @throws JsonException
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        $filterUserId = ($role === UserRole::ADMIN->value) ? null : $userId;

        $tickets = $this->repository->findAll($filterUserId);

        $this->json(['tickets' => $tickets]);
    }

    /**
     * @throws JsonException
     */
    public function store(): void
    {
        $input = $this->getJsonInput();

        $this->validate($input, [
            'title'       => 'required|string|min:2|max:255',
            'description' => 'required|string|min:1'
        ]);

        $statusId = $this->repository->findStatusIdByCode('todo');
        if (!$statusId) {
            $this->error('System error: Default status not found', 500);
        }

        $newId = $this->repository->create(
            $_SESSION['user_id'],
            $statusId,
            trim($input['title']),
            trim($input['description'])
        );

        if (isset($input['tags']) && is_array($input['tags'])) {
            $this->repository->attachTags($newId, $input['tags']);
        }

        $ticket = $this->repository->find($newId);

        $this->json(['message' => 'Ticket created', 'ticket' => $ticket], 201);
    }

    /**
     * @throws JsonException
     */
    public function show(string $id): void
    {
        $ticket = $this->repository->find((int)$id);

        if (!$ticket) {
            $this->error('Ticket not found', 404);
        }

        $this->checkAccess($ticket);

        $this->json(['ticket' => $ticket]);
    }

    /**
     * @throws JsonException
     */
    public function update(string $id): void
    {
        $input = $this->getJsonInput();

        $this->validate($input, [
            'title' => 'required|string|min:2|max:255',
            'status_code' => 'required|string|min:2|max:50',
            'description' => 'required|string|min:1'
        ]);

        $this->repository->update(
            (int)$id,
            $input['status_code'],
            $input['title'],
            $input['description'] ?? '',
            $input['tags'] ?? []
        );

        $this->json(['message' => 'Ticket updated']);
    }

    /**
     * @throws JsonException
     */
    public function updateStatus(string $id): void
    {
        $input = $this->getJsonInput();

        $this->validate($input, [
            'status_code' => 'required|string'
        ]);

        $newStatusId = $this->repository->findStatusIdByCode($input['status_code']);
        if (!$newStatusId) {
            $this->error('Invalid status code', 422);
        }

        $ticket = $this->repository->find((int)$id);
        if (!$ticket) {
            $this->error('Ticket not found', 404);
        }

        $this->checkAccess($ticket);

        $this->repository->updateStatus((int)$id, $newStatusId);
        $updatedTicket = $this->repository->find((int)$id);

        $this->json(['message' => 'Status updated', 'ticket' => $updatedTicket]);
    }

    /**
     * @throws JsonException
     */
    private function checkAccess(array $ticket): void
    {
        if (
            $_SESSION['role'] !== UserRole::ADMIN->value &&
            $ticket['user_id'] !== $_SESSION['user_id']
        ) {
            $this->error('Access denied', 403);
        }
    }
}