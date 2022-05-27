<?php

namespace App\Flare\ServerFight;

class BattleMessages {

    private array $battleMessages;

    public function __construct() {
        $this->battleMessages     = [];
    }

    public function addMessage(string $message, string $type) {
        $this->battleMessages[] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    public function mergeMessages(array $messages) {
        $this->battleMessages = [...$this->battleMessages, ...$messages];
    }

    public function getMessages() {
        return $this->battleMessages;
    }

    public function clearMessages() {
        $this->battleMessages = [];
    }
}
