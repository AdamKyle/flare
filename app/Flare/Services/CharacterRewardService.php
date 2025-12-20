<?php

namespace App\Flare\Services;

use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\CharacterService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use App\Game\Skills\Services\SkillService;
use Facades\App\Flare\Calculators\XPCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;


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
     *
     * @param Character $character
     * @return CharacterRewardService
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
     *
     * @param integer $xp
     * @return CharacterRewardService
     */
    public function distributeSpecifiedXp(int $xp): CharacterRewardService
    {

        $this->characterXpService->setCharacter($this->character)->distributeSpecifiedXp($xp);

        return $this;
    }

    /**
     * Distribute Skill Xp
     *
     * @param Monster $monster
     * @return CharacterRewardService
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
    public function giveCurrencies(Monster $monster): CharacterRewardService
    {
        $this->characterCurrencyRewardService->setCharacter($this->character)->giveCurrencies($monster);

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
     *
     * @param Monster $monster
     * @return integer
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
        return Location::whereNotNull('enemy_strength_type')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS)
            ->first();
    }
}
