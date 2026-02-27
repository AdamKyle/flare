<?php

namespace App\Game\Exploration\Jobs;

use App\Flare\Models\DelveExploration as DelveExplorationModel;
use App\Flare\Models\Location;
use App\Flare\Values\AutomationType;
use App\Game\Exploration\Values\DelveOutcome;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Skills\Services\SkillService;
use Psr\SimpleCache\InvalidArgumentException;

class DelveExploration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const MAX_ATTEMPTS = 10;

    const MAX_INCREASE_PERCENTAGE = 1000.00;

    public ?Character $character = null;

    public ?Location $location = null;

    private CharacterRewardService $characterRewardService;

    private SkillService $skillService;

    private MonsterFightService $monsterFightService;

    private FactionHandler $factionHandler;

    private ?Monster $monster = null;

    private int $automationId;

    private int $delveAutomationId;

    private string $attackType;

    private int $timeDelay;

    private int $packSize;

    private int $attempts = 0;

    private bool $showedEncounterMessage = false;

    private array $battleData = [];

    private array $lastFightData = [];

    private bool $logCreated = false;


    public function __construct(int $characterId, int $locationId, int $automationId, int $delveExplorationId, array $params, int $timeDelay)
    {
        $this->character = Character::find($characterId);
        $this->location = Location::find($locationId);
        $this->automationId = $automationId;
        $this->delveAutomationId = $delveExplorationId;
        $this->attackType = $params['attack_type'];
        $this->packSize = $params['pack_size'] ?? 1;
        $this->timeDelay = $timeDelay;
    }

    public function handle(
        MonsterFightService $monsterFightService,
        BattleEventHandler $battleEventHandler,
        CharacterCacheData $characterCacheData,
        CharacterRewardService $characterRewardService,
        SkillService $skillService,
        FactionHandler $factionHandler,
    ): void {

        $this->characterRewardService = $characterRewardService;

        $this->skillService = $skillService;

        $this->monsterFightService = $monsterFightService;

        $this->factionHandler = $factionHandler;

        $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

        $delveAutomation = DelveExplorationModel::where('character_id', $this->character->id)->where('id', $this->delveAutomationId)->first();

        if ($this->shouldBail($automation, $delveAutomation)) {
            $this->endAutomation($automation, $delveAutomation, $characterCacheData);

            Cache::delete('can-character-survive-' . $this->character->id);

            return;
        }

        $params = [
            'selected_monster_id' => $delveAutomation->monster_id,
            'attack_type' => $this->attackType,
            'pack_size' => $this->packSize,
        ];

        if ($this->encounter($delveAutomation, $params, $this->timeDelay)) {

            $time = now()->diffInMinutes($automation->completed_at);

            $delay = $time >= $this->timeDelay ? $this->timeDelay : ($time > 1 ? $time : 0);

            if ($delay === 0) {
                $this->endAutomation($automation, $delveAutomation, $characterCacheData);

                return;
            }

            $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id'], $this->battleData);


            $newStatIncreaseValue = $delveAutomation->increase_enemy_strength + $this->location->delve_enemy_strength_increase;

            if ($newStatIncreaseValue >= self::MAX_INCREASE_PERCENTAGE) {
                $newStatIncreaseValue = self::MAX_INCREASE_PERCENTAGE;
            }

            if ($delveAutomation->increase_enemy_strength !== self::MAX_INCREASE_PERCENTAGE) {
                $this->updateDelveAutomation($delveAutomation, [
                    'increase_enemy_strength' => $newStatIncreaseValue
                ]);
            }

            $this->updateMonsterForNextFight($delveAutomation);

            $delveAutomation = $delveAutomation->refresh();

            $this->deletePackCache();

            $params['selected_monster_id'] = $this->monster?->id ?? $delveAutomation->monster_id;

            DelveExploration::dispatch($this->character->id, $this->location->id, $this->automationId, $this->delveAutomationId, $params, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');

            return;
        }

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $this->createDelveLog($delveAutomation, DelveOutcome::TIMEOUT, $this->lastFightData);

            $automation->delete();

            $delveAutomation->update([
                'completed_at' => now(),
            ]);

            $this->sendOutEventLogUpdate('Seems the fight went on too long child. You are exhausted. Best to flee with what you managed to gain!');

            $character = $this->character->refresh();

            $this->rewardPlayer($character, $delveAutomation->refresh());

            $this->deletePackCache();

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));

            return;
        }

        $automation->delete();

        $delveAutomation->update([
            'completed_at' => now(),
        ]);

        event(new ExplorationTimeOut($this->character->user, 0));
    }

    private function deletePackCache(): void {
        Cache::delete('delve-monster-' . $this->character->id . '-' . $this->monster->id . '-fight');
    }

    private function updateMonsterForNextFight(DelveExplorationModel $delveExploration): void {
        $monsterId = Monster::where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('game_map_id', $this->character->map->game_map_id)
            ->whereNull('only_for_location_type')
            ->whereNull('raid_special_attack_type')
            ->inRandomOrder()
            ->first()
            ->id;

        $this->updateDelveAutomation($delveExploration, [
            'monster_id' => $monsterId
        ]);
    }

    private function updateDelveAutomation(DelveExplorationModel $delveExploration, array $data): void {
        $delveExploration->update($data);
    }

    /**
     * Handle an encounter.
     *
     * @param DelveExplorationModel $delveExploration
     * @param array $params
     * @param int $timeDelay
     * @return bool
     * @throws InvalidArgumentException
     */
    private function encounter(DelveExplorationModel $delveExploration, array $params, int $timeDelay): bool
    {

        $canSurviveFights = $this->canSurviveFight($delveExploration, $params);

        if ($canSurviveFights) {

            $this->createDelveLog($delveExploration, DelveOutcome::SURVIVED, $this->lastFightData);

            $this->sendOutEventLogUpdate('You survived the darkness child. Alas there is more of it to go. Find your way to the depths. Find the treasure!', true);

            return true;
        }

        return false;
    }

    /**
     * Fight and process rewards and return true or false.
     *
     * - Uses a cached version to make this faster.
     *
     * @param DelveExplorationModel $delveExploration
     * @param array $params
     * @return bool
     * @throws InvalidArgumentException
     */
    private function canSurviveFight(DelveExplorationModel $delveExploration, array $params): bool
    {

        $this->sendOutEventLogUpdate('Before you in the darkness, lies a beast unknown to man. Kill it child. Slaughter it!');

        $packSize = $params['pack_size'];

        if ($packSize > 1) {

            return $this->fightMultipleEnemies($delveExploration, $params);
        }

        return $this->fightAutomationMonster($delveExploration, $params);

    }

    private function fightMultipleEnemies(DelveExplorationModel $delveExploration, array $params): bool {
        $totalXpToReward = 0;
        $totalSkillXpToReward = 0;
        $totalFactionPoints = 0;
        $characterRewardService = $this->characterRewardService->setCharacter($this->character);
        $characterSkillService = $this->skillService->setSkillInTraining($this->character);

        $packSize = $params['pack_size'];

        for ($i = 1; $i <= $packSize; $i++) {
            $survived = $this->fightAutomationMonster($delveExploration, $params);

            if (!$survived) {
                return false;
            }

            $params['selected_monster_id'] = $this->monster?->id ?? $params['selected_monster_id'];

            $totalXpToReward += $characterRewardService->fetchXpForMonster($this->monster);
            $totalSkillXpToReward += $characterSkillService->getXpForSkillIntraining($this->character, $this->monster->xp);
            $totalFactionPoints += $this->factionHandler->getFactionPointsPerKill($this->character);
        }

        $this->battleData = [
            'total_creatures' => $params['pack_size'],
            'total_xp' => $this->getPackSizeXp($packSize, $totalXpToReward),
            'total_faction_points' => $totalFactionPoints,
            'total_skill_xp' => $this->getPackSizeXp($packSize, $totalSkillXpToReward),
        ];

        return true;
    }

    private function getPackSizeXp(int $packSize, int $xp): int {
        return match($packSize) {
            5 => $xp + ($xp * 1.0),
            10 => $xp + ($xp * 1.25),
            20 => $xp + ($xp * 1.50),
            25 => $xp + ($xp * 1.75),
            default => $xp
        };
    }

    /**
     * Fight monster through automation.
     *
     * @param DelveExplorationModel $delveExploration
     * @param array $params
     * @return bool
     * @throws InvalidArgumentException
     */
    private function fightAutomationMonster(DelveExplorationModel $delveExploration, array $params): bool
    {

        $data = $this->monsterFightService->setupMonster($this->character, $params, true, true);

        $this->monster = $this->monsterFightService->getMonster();

        $this->showEverBurningMessages($delveExploration->increase_enemy_strength, $this->monster->name, $params['pack_size']);

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($delveExploration, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        $data = $this->fightMonster($delveExploration);

        if (empty($data)) {
            return false;
        }

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($delveExploration, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        return true;
    }

    private function showEverBurningMessages(float $increaseAmount, string $monsterName, int $packSize): void {

        if ($packSize > 1 && $this->showedEncounterMessage) {
            return;
        }

        $this->sendOutEventLogUpdate('The Ever Burning Candle erupts forward and the light illuminates the foul beast: ' . $monsterName);

        if ($packSize > 1) {
            $this->sendOutEventLogUpdate('Holy shit child, there are ' . $packSize . ' of them. Hold your ground!');
        }

        if ($increaseAmount > 0) {
            $percent = $increaseAmount * 100;

            $this->sendOutEventLogUpdate("The beast(s) is radiant with magic, you know its strength has increased by: " . $percent . '%');
        }

        $this->showedEncounterMessage = true;
    }

    /**
     * Handle when a character dies in automation.
     *
     * @param DelveExplorationModel $delveExploration
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private function handleWhenCharacterDies(DelveExplorationModel $delveExploration, array $data): bool {

        if ($data['health']['current_character_health'] <= 0) {

            $this->createDelveLog($delveExploration, DelveOutcome::DIED, $data);

            $delveExploration->update([
                'completed_at' => now(),
            ]);

            CharacterAutomation::where('character_id', $delveExploration->character_id)->where('type', AutomationType::DELVE)->delete();

            $this->sendOutEventLogUpdate('You died during the delve. Exploration has ended, but not all is lost, you awaken from your wounds there might be treasures waiting, treasures you collected. (See server messages for treasures)');

            $this->rewardPlayer($this->character, $delveExploration->refresh());

            $this->deletePackCache();

            event(new ExplorationTimeOut($this->character->user, 0));

            return true;
        }

        return false;
    }

    private function shouldAttackAgain(array $data): bool {

        if ($data['health']['current_monster_health'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Should we bail?
     *
     * @param CharacterAutomation|null $automation
     * @param DelveExplorationModel|null $delveExploration
     * @return bool
     */
    private function shouldBail(?CharacterAutomation $automation = null, ?DelveExplorationModel $delveExploration = null): bool
    {

        if (is_null($this->character) || is_null($this->location)) {
            return true;
        }

        if (is_null($automation)) {
            return true;
        }

        if (is_null($delveExploration)) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        if (!is_null($delveExploration->completed_at)) {
            return true;
        }

        return false;
    }

    /**
     * End automation.
     *
     * @param CharacterAutomation|null $automation
     * @param DelveExplorationModel|null $delveExploration
     * @param CharacterCacheData $characterCacheData
     * @return void
     * @throws Exception
     */
    private function endAutomation(?CharacterAutomation $automation, ?DelveExplorationModel $delveExploration, CharacterCacheData $characterCacheData): void
    {
        $characterCacheData->deleteCharacterSheet($this->character);

        if (! is_null($automation)) {
            $automation->delete();

            $character = $this->character->refresh();

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));
        }

        if (! is_null($delveExploration)) {

            if (!is_null($delveExploration->completed_at)) {
                return;
            }

            $delveExploration->update([
                'completed_at' => now(),
            ]);

            $this->sendOutEventLogUpdate('You climb from the depths of the delve exploration, covered in blood, grime, dirt. Carrying the treasures you went searching for. Maybe now you have more answers about the darkness, or maybe you have more trauma.', true);

            $this->sendOutEventLogUpdate('Your adventure is over child. Now is the time to rest, relax, heal and sort through your haul to weed out the worthless.', true);

            $this->rewardPlayer($character, $delveExploration->refresh());
        }
    }

    /**
     * Fight the monster.
     *
     * @param DelveExplorationModel $delveExploration
     * @return array
     * @throws InvalidArgumentException
     */
    private function fightMonster(DelveExplorationModel $delveExploration): array {
        $data = $this->monsterFightService->fightMonster($this->character, $this->attackType, false, true);

        $this->lastFightData = $data;

        if ($this->shouldAttackAgain($data) && $this->attempts >= self::MAX_ATTEMPTS) {
            $this->sendOutEventLogUpdate('Seems this beast is a little stronger then normal. You swing again and lash out your magics.', true);

            return [];
        }

        if ($this->shouldAttackAgain($data) && $this->attempts < self::MAX_ATTEMPTS) {
            $this->attempts++;

            return $this->fightMonster($delveExploration);
        }

        return $data;
    }

    private function createDelveLog(DelveExplorationModel $delveExploration, DelveOutcome $outcome, array $fightData): void
    {
        if ($this->logCreated) {
            return;
        }

        $delveExploration->delveLogs()->create([
            'character_id' => $this->character->id,
            'increased_enemy_strength' => $delveExploration->increase_enemy_strength,
            'delve_exploration_id' => $delveExploration->id,
            'pack_size' => $this->packSize,
            'outcome' => $outcome->value,
            'fight_data' => $fightData,
        ]);

        $this->logCreated = true;
    }

    /**
     * Send out event log updates
     *
     * @param string $message
     * @param bool $makeItalic
     * @param bool $isReward
     * @return void
     */
    private function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void
    {
        if ($this->character->isLoggedIn()) {
            event(new ExplorationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
        }
    }

    private function sendServerMessage(string $message, int $itemId): void {

        if ($this->character->isLoggedIn()) {
            event(new ServerMessageEvent($this->character->user, $message, $itemId));
        }
    }

    /**
     * Reward the player for automation completion.
     *
     * @param Character $character
     * @param DelveExplorationModel $delveExploration
     * @return void
     * @throws Exception
     */
    private function rewardPlayer(Character $character, DelveExplorationModel $delveExploration): void
    {

        $start = $delveExploration->started_at;
        $end = $delveExploration->completed_at;

        $timeElapsedInHours = $end->diffInHours($start);

        $cosmicItem = null;
        $mythicItem = null;
        $uniqueItem = null;

        if ($timeElapsedInHours > 6) {
            $cosmicItem = $this->characterRewardService->getSpecialGearDrop(RandomAffixDetails::COSMIC);
        }

        if ($timeElapsedInHours > 4 && $timeElapsedInHours < 6) {
            $mythicItem = $this->characterRewardService->getSpecialGearDrop(RandomAffixDetails::MYTHIC);
        }

        if ($timeElapsedInHours > 2) {
            $uniqueItem = $this->characterRewardService->getSpecialGearDrop(RandomAffixDetails::LEGENDARY);
        }

        $gold = 1_000;

        if (!is_null($cosmicItem)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $cosmicItem->id
            ]);

            $gold = $character->gold + 1_000_000_000_000;

            $this->sendOutEventLogUpdate('Gained one trillion gold for completing the delve.', false, true);

            $this->sendOutEventLogUpdate('Gained a cosmic item child! (Check Server Messages).', false, true);

            $this->sendServerMessage('You were rewarded with a cosmic item: ' . $cosmicItem->affix_name . ' for surviving for more then 6 hours in a delve!', $slot->id);
        }

        if (!is_null($mythicItem)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $mythicItem->id
            ]);

            $gold = $character->gold + 1_000_000_000;

            $this->sendOutEventLogUpdate('Gained one billion gold for completing the delve.', false, true);

            $this->sendOutEventLogUpdate('Gained a mythic item child! (Check Server Messages).', false, true);

            $this->sendServerMessage('You were rewarded with a mythic item: ' . $mythicItem->affix_name . ' for surviving for more then 4 hours in a delve!', $slot->id);
        }

        if (!is_null($uniqueItem)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $uniqueItem->id
            ]);

            $gold = $character->gold + 1_000_000;

            $this->sendOutEventLogUpdate('Gained one million gold for completing the delve.', false, true);

            $this->sendOutEventLogUpdate('Gained a unique item child! (Check Server Messages).', false, true);

            $this->sendServerMessage('You were rewarded with a unique item: ' . $uniqueItem->affix_name . ' for surviving for more then 2 hours in a delve!', $slot->id);
        }

        if ($gold === 1_000) {
            $gold = $character->gold + $gold;

            $this->sendOutEventLogUpdate('Gained one thousand gold for completing the delve.', false, true);
        }

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update(['gold' => $gold]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));

    }
}
