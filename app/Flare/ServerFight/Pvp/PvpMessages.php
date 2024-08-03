<?php

namespace App\Flare\ServerFight\Pvp;

class PvpMessages
{
    private $attackerMessages = [];

    private $defenderMessages = [];

    public function addAttackerMessage(string $message, string $type)
    {
        $this->attackerMessages[] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    public function addDefenderMessage(string $message, string $type)
    {
        $this->defenderMessages[] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    public function mergeAttackerMessages(array $messages)
    {
        $this->attackerMessages = array_merge($this->attackerMessages, $messages);
    }

    public function mergeDefenderMessages(array $messages)
    {
        $this->defenderMessages = array_merge($this->defenderMessages, $messages);
    }

    public function getAttackerMessages()
    {
        return $this->attackerMessages;
    }

    public function getDefenderMessages()
    {
        return $this->defenderMessages;
    }

    public function clearPvpMessage()
    {
        $this->attackerMessages = [];
        $this->defenderMessages = [];
    }
}
