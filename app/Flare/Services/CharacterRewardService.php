<?php

namespace App\Flare\Services;

use App\Flare\Calculators\XPCalculator as CalculatorsXPCalculator;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\CharacterService;
use Facades\App\Flare\Calculators\XPCalculator;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterRewardService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var CharacterXPService $characterXpService
     */
    private $characterXpService;

    private $manager;

    private $characterSheetBaseInfoTransformer;

    /**
     * Constructor
     *
     * @param CharacterXPService $characterXpService
     */
    public function __construct(CharacterXPService $characterXpService, CharacterService $characterService, Manager $manager, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer) {
        $this->characterXpService                = $characterXpService;
        $this->characterService                  = $characterService;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
        $this->manager                           = $manager;
    }

    public function setCharacter(Character $character): CharacterRewardService {
        $this->character = $character;

        return $this;
    }

    /**
     * Distribute the gold and xp to the character.
     *
     * @param Monster $monster
     * @param Adventure $adventure | null
     * @return void
     */
    public function distributeGoldAndXp(Monster $monster, Adventure $adventure = null) {
        $this->distributeXP($monster, $adventure);

        if ($this->character->xp >= $this->character->xp_next) {
            $this->handleCharacterLevelUp();
        }

        $this->distributeGold($monster);
    }

    /**
     * Get the refreshed Character
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    /**
     * Get the skill in training or null
     *
     * @return mixed
     */
    public function fetchCurrentSkillInTraining() {
        return $this->character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();
    }

    /**
     * Fire the update skill event.
     *
     * @param Skill $skill
     * @param Adventure $adventure | nul
     * @return void
     */
    public function trainSkill(Skill $skill, Adventure $adventure = null, Monster $monster = null) {
        event(new UpdateSkillEvent($skill, $adventure, $monster));
    }

    /**
     * Assigns XP to the character.
     *
     * @param Monster $monster
     * @param Adventure|null $adventure
     * @return void
     */
    protected function distributeXP(Monster $monster, Adventure $adventure = null) {
        $currentSkill = $this->fetchCurrentSkillInTraining();
        $xpReduction  = 0.0;
        $gameMap      = $this->character->map->gameMap;

        if (!is_null($currentSkill)) {
            $xpReduction = $currentSkill->xp_towards;

            $this->trainSkill($currentSkill, $adventure, $monster);
        }

        $xp = XPCalculator::fetchXPFromMonster($monster, $this->character->level, $xpReduction);
        $xp = $this->characterXpService->determineXPToAward($this->character, $xp);

        if (!is_null($gameMap->xp_bonus)) {
            $xp = $xp * (1 + $gameMap->xp_bonus);
        }

        $xp = $this->character->xp + $xp;

        $this->character->update([
            'xp' => $xp
        ]);

        $this->character = $this->character->refresh();
    }

    protected function handleCharacterLevelUp() {
        $this->characterService->levelUpCharacter($this->character);

        $character = $this->character->refresh();

        CharacterAttackTypesCacheBuilder::dispatch($character);

        event(new ServerMessageEvent($character->user, 'level_up'));
        event(new UpdateTopBarEvent($character));

        $characterData = new Item($character, $this->characterSheetBaseInfoTransformer);
        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    /**
     * Gives gold to the player.
     *
     * @param Monster $monster
     * @return void
     * @throws \Exception
     */
    protected function distributeGold(Monster $monster) {
        $newGold       = $this->character->gold + $monster->gold;
        $maxCurrencies = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);


        if (!$maxCurrencies->canNotGiveCurrency()) {
            $this->character->update(['gold' => $newGold]);
        }
    }
}
