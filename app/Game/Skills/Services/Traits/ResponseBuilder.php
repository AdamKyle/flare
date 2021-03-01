<?php

namespace App\Game\Skills\Services\Traits;

trait ResponseBuilder {

    /**
     * Build the error result.
     * 
     * @param string $message
     * @return array
     */
    public function errorResult(string $message): array {
        return  [
            'message' => $message,
            'status'  => 422,
        ];
    }

    /**
     * Build the success result.
     * 
     * @param array $success
     * @return array
     */
    public function successResult(array $success): array {
        return array_merge([
            'status' => 200
        ], $success);
    }
}