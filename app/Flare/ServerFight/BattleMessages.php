<?php

namespace App\Flare\ServerFight;

use App\Flare\ServerFight\Pvp\PvpMessages;

class BattleMessages extends PvpMessages {

    private array $battleMessages;

    public function __construct() {
        $this->battleMessages     = [];
    }

    public function addMessage(string $message, string $type, bool $addToAttackerPvp = false) {

        if ($addToAttackerPvp) {
            $this->addAttackerMessage($message, $type);

            return;
        }

        $this->battleMessages[] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    public function mergeMessages(array $messages) {
        $this->battleMessages = array_merge($this->battleMessages, $messages);
    }

    public function getMessages() {
        return $this->battleMessages;
    }

    public function clearMessages() {
        $this->battleMessages = [];

        $this->clearPvpMessage();
    }
}
