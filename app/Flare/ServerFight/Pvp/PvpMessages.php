<?php

namespace App\Flare\ServerFight\Pvp;

class PvpMessages {

    private $attackerMessages = [];

    private $defenderMessages = [];

    public function addAttackerMessage(string $message, string $type) {
        $this->attackerMessages[] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    public function addDefenderMessage(string $message, string $type) {
        $this->defenderMessages[] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    public function mergeAttackerMessages(array $messages) {
        $this->attackerMessages = [...$this->attackerMessages, ...$messages];
    }

    public function mergeDefenderMessages(array $messages) {
        $this->defenderMessages = [...$this->defenderMessages, ...$messages];
    }

    public function getAttackerMessages() {
        return $this->attackerMessages;
    }

    public function getDefenderMessages() {
        return $this->defenderMessages;
    }

    public function clearMessages() {
        $this->attackerMessages = [];
        $this->defenderMessages = [];
    }
}
