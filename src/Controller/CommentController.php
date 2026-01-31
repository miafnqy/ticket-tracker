<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\TicketRepository;
use App\Enums\UserRole;
use JsonException;

class CommentController extends BaseController
{
    private CommentRepository $commentRepo;
    private TicketRepository $ticketRepo;

    public function __construct()
    {
        $this->commentRepo = new CommentRepository();
        $this->ticketRepo = new TicketRepository();
    }

    /**
     * @throws JsonException
     */
    public function index(string $ticketId): void
    {
        $this->ensureTicketAccess((int)$ticketId);

        $comments = $this->commentRepo->findAllByTicketId((int)$ticketId);

        $this->json(['comments' => $comments]);
    }

    /**
     * @throws JsonException
     */
    public function store(string $ticketId): void
    {
        $input = $this->getJsonInput();

        $this->validate($input, [
            'text' => 'required|string|min:1'
        ]);

        $this->ensureTicketAccess((int)$ticketId);

        $newId = $this->commentRepo->create(
            (int)$ticketId,
            $_SESSION['user_id'],
            trim($input['text'])
        );

        $comment = $this->commentRepo->find($newId);

        $this->json(['message' => 'Comment added', 'comment' => $comment], 201);
    }

    /**
     * @throws JsonException
     */
    private function ensureTicketAccess(int $ticketId): void
    {
        $ticket = $this->ticketRepo->find($ticketId);

        if (!$ticket) {
            $this->error('Ticket not found', 404);
        }

        if ($_SESSION['role'] === UserRole::ADMIN->value) {
            return;
        }

        if ($ticket['user_id'] !== $_SESSION['user_id']) {
            $this->error('Access denied', 403);
        }
    }
}