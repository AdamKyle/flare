<?php

namespace App\Game\Battle\ServerFight\Monster;

use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;

class ServerMonster
{
    private int $health;

    private array $monster;

    /**
     * @param ChanceCalculator $chanceCalculator
     * @param RandomNumberGenerator $randomNumberGenerator
     */
    public function __construct(
        private readonly ChanceCalculator $chanceCalculator,
        private readonly RandomNumberGenerator $randomNumberGenerator,
    ) {}

    /**
     * Set the Monster's current health for the fight.
     *
     * @param int $health
     * @return ServerMonster
     */
    public function setHealth(int $health): ServerMonster
    {
        $this->health = $health;

        return $this;
    }

    /**
     * Set the cached Monster payload this fight object reads from.
     *
     * @param array $monster
     * @return ServerMonster
     */
    public function setMonster(array $monster): ServerMonster
    {
        $this->monster = $monster;

        return $this;
    }

    /**
     * Determine whether the Monster's devouring darkness overcomes the Character's resistance.
     *
     * @param float $devouringDarknessResistance
     * @return bool
     */
    public function canMonsterDevoidPlayer(float $devouringDarknessResistance): bool
    {
        $chance = $this->monster['devouring_darkness_chance'];

        if ($devouringDarknessResistance > $chance) {
            return false;
        }

        $chance -= $devouringDarknessResistance;

        if ($chance >= 1) {
            return true;
        }

        return $this->chanceCalculator->passesPercentage($chance * 100);
    }

    /**
     * Determine whether the Monster's devouring light overcomes the Character's resistance.
     *
     * @param float $devouringLightResistance
     * @return bool
     */
    public function canMonsterVoidPlayer(float $devouringLightResistance): bool
    {
        $chance = $this->monster['devouring_light_chance'];

        if ($devouringLightResistance > $chance) {
            return false;
        }

        $chance -= $devouringLightResistance;

        if ($chance >= 1) {
            return true;
        }

        return $this->chanceCalculator->passesPercentage($chance * 100);
    }

    /**
     * Roll the Monster's attack from its attack range, including its damage increase.
     *
     * @return int
     */
    public function buildAttack(): int
    {
        $attackArray = explode('-', $this->monster['attack_range']);

        $attack = $this->randomNumberGenerator->numberBetween($attackArray[0], $attackArray[1]);

        $increasesHealthBy = $this->monster['increases_damage_by'];

        if (! is_null($increasesHealthBy)) {
            $attack = $attack + $attack * $increasesHealthBy;
        }

        return $attack;
    }

    /**
     * Return the Monster id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->monster['id'];
    }

    /**
     * Return the Monster name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->monster['name'];
    }

    /**
     * Determine whether the Monster is a raid boss.
     *
     * @return bool
     */
    public function isRaidBossMonster(): bool
    {
        return $this->monster['is_raid_boss'];
    }

    /**
     * Return one value from the cached Monster payload.
     *
     * @param string $key
     * @return mixed
     */
    public function getMonsterStat(string $key): mixed
    {
        return $this->monster[$key];
    }

    /**
     * Return the cached Monster payload.
     *
     * @return array
     */
    public function getMonster(): array
    {
        return $this->monster;
    }

    /**
     * Return the Monster's current health.
     *
     * @return int
     */
    public function getHealth(): int
    {
        return $this->health;
    }

    /**
     * Return the Monster's Fire, Ice, and Water atonement.
     *
     * @return array
     */
    public function getElementData(): array
    {
        $fire = is_null($this->monster['fire_atonement']) ? 0 : $this->monster['fire_atonement'];
        $ice = is_null($this->monster['ice_atonement']) ? 0 : $this->monster['ice_atonement'];
        $water = is_null($this->monster['water_atonement']) ? 0 : $this->monster['water_atonement'];

        return [
            'fire' => $fire,
            'ice' => $ice,
            'water' => $water,
        ];
    }

    /**
     * Return the Monster's cached Fire, Ice, and Water elemental penetration.
     *
     * @return array
     */
    public function getElementalPenetrationData(): array
    {
        return [
            'fire' => $this->monster['fire_penetration'] ?? 0.0,
            'ice' => $this->monster['ice_penetration'] ?? 0.0,
            'water' => $this->monster['water_penetration'] ?? 0.0,
        ];
    }

    /**
     * Determine whether the Monster has positive atonement in every element.
     *
     * @return bool
     */
    public function canMonsterUseElementalAttack(): bool
    {
        return $this->monster['fire_atonement'] > 0 &&
            $this->monster['ice_atonement'] > 0 &&
            $this->monster['water_atonement'] > 0;
    }
}
