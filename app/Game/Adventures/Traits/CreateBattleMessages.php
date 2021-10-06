<?php

namespace App\Game\Adventures\Traits;

trait CreateBattleMessages {

    /**
     * Adds a new message to the existing array of battle messages.
     *
     * @param string $message
     * @param string $class
     * @param array $battleMessages
     * @return array
     */
    public function addMessage(string $message, string $class, array $battleMessages = []): array {
        $battleMessages[] = [
            'message' => $message,
            'class'   => $class,
        ];

        return $battleMessages;
    }
}