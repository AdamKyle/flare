<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Services\CharacterService;
use App\Game\Skills\Services\SkillService;
use Exception;
use League\Fractal\Manager;

class CharacterRewardService
{
    private Character $character;

    private CharacterService $characterService;

    private SkillService $skillService;

    private CharacterXPService $characterXpService;

    private Manager $manager;

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    private BattleMessageHandler $battleMessageHandler;

    private CharacterCurrencyRewardService $characterCurrencyRewardService;

    /**
     * Constructor
     */
    public function __construct(
        CharacterXPService $characterXpService,
        CharacterCurrencyRewardService $characterCurrencyRewardService,
        CharacterService $characterService,
        SkillService $skillService,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        BattleMessageHandler $battleMessageHandler,
    ) {
        $this->characterXpService = $characterXpService;
        $this->characterService = $characterService;
        $this->skillService = $skillService;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
        $this->manager = $manager;
        $this->battleMessageHandler = $battleMessageHandler;
        $this->characterCurrencyRewardService = $characterCurrencyRewardService;
    }

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
     * Are we at a location with an effect (special location)?
     */
    private function purgatoryDungeons(Map $map): ?Location
    {
        return Location::whereNotNull('enemy_strength_increase')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS)
            ->first();
    }
}
