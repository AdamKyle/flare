<?php

namespace App\Game\Character\CharacterCreation\State;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use DateTimeInterface;

class CharacterBuildState
{
    private ?User $user = null;

    private ?GameRace $race = null;

    private ?GameClass $class = null;

    private ?GameMap $map = null;

    private ?Character $character = null;

    private ?DateTimeInterface $now = null;

    private ?string $characterName = null;

    public function setUser(User $user): CharacterBuildState
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setRace(GameRace $race): CharacterBuildState
    {
        $this->race = $race;

        return $this;
    }

    public function getRace(): ?GameRace
    {
        return $this->race;
    }

    public function setClass(GameClass $class): CharacterBuildState
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?GameClass
    {
        return $this->class;
    }

    public function setMap(GameMap $map): CharacterBuildState
    {
        $this->map = $map;

        return $this;
    }

    public function getMap(): ?GameMap
    {
        return $this->map;
    }

    public function setCharacter(Character $character): CharacterBuildState
    {
        $this->character = $character;

        return $this;
    }

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setNow(DateTimeInterface $now): CharacterBuildState
    {
        $this->now = $now;

        return $this;
    }

    public function getNow(): ?DateTimeInterface
    {
        return $this->now;
    }

    public function setCharacterName(string $characterName): CharacterBuildState
    {
        $this->characterName = $characterName;

        return $this;
    }

    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }
}
