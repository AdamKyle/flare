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
    /**
     * @var User|null $user
     */
    private ?User $user = null;

    /**
     * @var GameRace|null $race
     */
    private ?GameRace $race = null;

    /**
     * @var GameClass|null $class
     */
    private ?GameClass $class = null;

    /**
     * @var GameMap|null $map
     */
    private ?GameMap $map = null;

    /**
     * @var Character|null $character
     */
    private ?Character $character = null;

    /**
     * @var DateTimeInterface|null $now
     */
    private ?DateTimeInterface $now = null;

    /**
     * @var string|null $characterName
     */
    private ?string $characterName = null;

    /**
     * @param User $user
     * @return CharacterBuildState
     */
    public function setUser(User $user): CharacterBuildState
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param GameRace $race
     * @return CharacterBuildState
     */
    public function setRace(GameRace $race): CharacterBuildState
    {
        $this->race = $race;

        return $this;
    }

    /**
     * @return GameRace|null
     */
    public function getRace(): ?GameRace
    {
        return $this->race;
    }

    /**
     * @param GameClass $class
     * @return CharacterBuildState
     */
    public function setClass(GameClass $class): CharacterBuildState
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return GameClass|null
     */
    public function getClass(): ?GameClass
    {
        return $this->class;
    }

    /**
     * @param GameMap $map
     * @return CharacterBuildState
     */
    public function setMap(GameMap $map): CharacterBuildState
    {
        $this->map = $map;

        return $this;
    }

    /**
     * @return GameMap|null
     */
    public function getMap(): ?GameMap
    {
        return $this->map;
    }

    /**
     * @param Character $character
     * @return CharacterBuildState
     */
    public function setCharacter(Character $character): CharacterBuildState
    {
        $this->character = $character;

        return $this;
    }

    /**
     * @return Character|null
     */
    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    /**
     * @param DateTimeInterface $now
     * @return CharacterBuildState
     */
    public function setNow(DateTimeInterface $now): CharacterBuildState
    {
        $this->now = $now;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getNow(): ?DateTimeInterface
    {
        return $this->now;
    }

    /**
     * @param string $characterName
     * @return CharacterBuildState
     */
    public function setCharacterName(string $characterName): CharacterBuildState
    {
        $this->characterName = $characterName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }
}
