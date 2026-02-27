<?php

namespace App\Flare\Services;

use App\Flare\Items\Builders\BuildCosmicItem;
use App\Flare\Items\Builders\BuildMythicItem;
use App\Flare\Items\Builders\BuildUniqueItem;
use App\Flare\Models\Character;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Monster;
use App\Game\Skills\Services\SkillService;
use Exception;

class CharacterRewardService
{
    private ?Character $character = null;

    /**
     * Constructor
     */
    public function __construct(
        private readonly CharacterXPService $characterXpService,
        private readonly CharacterCurrencyRewardService $characterCurrencyRewardService,
        private readonly SkillService $skillService,
        private readonly BuildUniqueItem $buildUniqueItem,
        private readonly BuildMythicItem $buildMythicItem,
        private readonly BuildCosmicItem $buildCosmicItem,
    ) {}

    /**
     * Set the character.
     */
    public function setCharacter(Character $character): CharacterRewardService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @throws Exception
     */
    public function distributeCharacterXP(Monster $monster): CharacterRewardService
    {
        $this->characterXpService->setCharacter($this->character)->distributeCharacterXP($monster);

        return $this;
    }

    /**
     * Distribute a specific amount of XP
     */
    public function distributeSpecifiedXp(int $xp): CharacterRewardService
    {

        $this->characterXpService->setCharacter($this->character)->distributeSpecifiedXp($xp);

        return $this;
    }

    /**
     * Distribute Skill Xp
     *
     * @throws Exception
     */
    public function distributeSkillXP(Monster $monster): CharacterRewardService
    {
        $this->skillService->setSkillInTraining($this->character)->assignXPToTrainingSkill($this->character, $monster->xp);

        return $this;
    }

    /**
     * Give currencies.
     *
     * @throws Exception
     */
    public function giveCurrencies(Monster $monster, $totalKills = 1): CharacterRewardService
    {
        $this->characterCurrencyRewardService->setCharacter($this->character)->giveCurrencies($monster, $totalKills);

        return $this;
    }

    /**
     * Get the refreshed Character
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Fetch the xp for the monster
     *
     * - Can return 0 if we cannot gain xp.
     * - Can return 0 if the xp we would gain is 0.
     * - Takes into account skills in training
     * - Takes into account Xp Bonuses such as items (Alchemy and quest)
     */
    public function fetchXpForMonster(Monster $monster): int
    {
        return $this->characterXpService->setCharacter($this->character)->fetchXpForMonster($monster);
    }

    /**
     * @throws Exception
     */
    public function getSpecialGearDrop(int $amountPaid): ItemModel
    {
        return Location::whereNotNull('enemy_strength_increase')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS)
            ->first();
    }
}
