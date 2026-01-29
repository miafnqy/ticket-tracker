<?php

declare(strict_types=1);

namespace App\Controller;

use JsonException;

class BaseController
{
    /**
     * @throws JsonException
     */
    protected function json(mixed $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @throws JsonException
     */
    protected function error(string $message, int $status = 400): void
    {
        $this->json(['error' => $message], $status);
    }

    protected function getJsonInput(): array
    {
        $content = file_get_contents('php://input');
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}