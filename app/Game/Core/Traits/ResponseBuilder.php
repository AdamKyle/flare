<?php

namespace App\Game\Core\Traits;

trait ResponseBuilder
{
    /**
     * Build the error result.
     */
    public function errorResult(string $message): array
    {
        return [
            'message' => $message,
            'status' => 422,
        ];
    }

    /**
     * Build the success result.
     */
    public function successResult(array $success = []): array
    {
        return array_merge([
            'status' => 200,
        ], $success);
    }
}
