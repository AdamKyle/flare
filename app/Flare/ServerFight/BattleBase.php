<?php

namespace App\Flare\ServerFight;

class BattleBase {

    private array $battleMessages;

    public function __construct() {
        $this->battleMessages = [];
    }

    public function addMessage(string $message, string $type) {
        $this->battleMessages[] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    public function getMessages() {
        return $this->battleMessages;
    }
}
