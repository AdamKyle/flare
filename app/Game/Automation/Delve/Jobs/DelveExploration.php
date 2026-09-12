<?php

namespace App\Game\Automation\Delve\Jobs;

use App\Admin\Events\DelveMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration as DelveExplorationModel;
use App\Flare\Models\Inventory;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\Automation\Delve\Enums\DelveOutcome;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Services\CharacterRewardService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;
use App\Game\Tops\Services\BroadcastTopsUpdateService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class DelveExploration implements ShouldQueue
{
    const float ENEMY_STRENGTH_INCREMENT = 0.05;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const int MAX_ATTEMPTS = 10;

    const float MAX_INCREASE_PERCENTAGE = 1000.00;

    public ?Character $character = null;

    public ?Location $location = null;

    private CharacterRewardService $characterRewardService;

    private SkillService $skillService;

    private MonsterFightService $monsterFightService;

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

    private BroadcastTopsUpdateService $broadcastTopsUpdateService;

    /**
     * @param int $characterId The character id delving.
     * @param int $locationId The Delve location id.
     * @param int $automationId The character automation id.
     * @param int $delveExplorationId The Delve exploration record id.
     * @param array $params The Delve fight parameters.
     * @param int $timeDelay The delay, in minutes, before this round runs.
     */
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

    /**
     * Run one Delve automation round: fight the encounter, apply rewards, and re-dispatch or end the run.
     *
     * @param MonsterFightService $monsterFightService The monster fight service.
     * @param BattleEventHandler $battleEventHandler The battle event handler.
     * @param CharacterCacheData $characterCacheData The character cache data service.
     * @param CharacterRewardService $characterRewardService The character reward service.
     * @param SkillService $skillService The skill service.
     * @param BroadcastTopsUpdateService $broadcastTopsUpdateService The tops broadcast service.
     * @return void This method does not return a value.
     */
    public function handle(
        MonsterFightService $monsterFightService,
        BattleEventHandler $battleEventHandler,
        CharacterCacheData $characterCacheData,
        CharacterRewardService $characterRewardService,
        SkillService $skillService,
        BroadcastTopsUpdateService $broadcastTopsUpdateService,
    ): void {

        $this->characterRewardService = $characterRewardService;

        $this->skillService = $skillService;

        $this->monsterFightService = $monsterFightService;

        $this->broadcastTopsUpdateService = $broadcastTopsUpdateService;

        if (is_null($this->character)) {
            return;
        }

        if (! Inventory::where('character_id', $this->character->id)->exists()) {
            $this->character->user()->update(['will_be_deleted' => true]);
            Log::warning('Delve exploration stopped for a character with missing inventory.', [
                'job' => self::class,
                'character_id' => $this->character->id,
                'user_id' => $this->character->user_id,
                'automation_id' => $this->automationId,
                'delve_exploration_id' => $this->delveAutomationId,
            ]);

            return;
        }

        $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

        $delveAutomation = DelveExplorationModel::where('character_id', $this->character->id)->where('id', $this->delveAutomationId)->first();

        try {
            if ($this->shouldBail($automation, $delveAutomation)) {
                $this->endAutomation($automation, $delveAutomation, $characterCacheData);

                Cache::delete('can-character-survive-'.$this->character->id);

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

                $newStatIncreaseValue = $delveAutomation->increase_enemy_strength + self::ENEMY_STRENGTH_INCREMENT;

                if ($newStatIncreaseValue >= self::MAX_INCREASE_PERCENTAGE) {
                    $newStatIncreaseValue = self::MAX_INCREASE_PERCENTAGE;
                }

                if ($delveAutomation->increase_enemy_strength !== self::MAX_INCREASE_PERCENTAGE) {
                    $this->updateDelveAutomation($delveAutomation, [
                        'increase_enemy_strength' => $newStatIncreaseValue,
                    ]);
                }

                $this->updateMonsterForNextFight($delveAutomation);

                $delveAutomation = $delveAutomation->refresh();

                $this->deletePackCache();

                $params['selected_monster_id'] = $this->monster?->id ?? $delveAutomation->monster_id;

                DelveExploration::dispatch($this->character->id, $this->location->id, $this->automationId, $this->delveAutomationId, $params, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onConnection('long_running')->onQueue('default_long');

                return;
            }

            if (is_null(CharacterAutomation::where('character_id', $this->character->id)->where('type', AutomationType::DELVE->value)->first())) {
                return;
            }

            $this->createDelveLog($delveAutomation, DelveOutcome::TIMEOUT, $this->lastFightData);

            $automation->delete();

            $delveAutomation->update([
                'completed_at' => now(),
                'ended_reason' => DelveOutcome::TIMEOUT->value,
                'panel_dismissed_at' => null,
            ]);

            $this->sendOutEventLogUpdate('Seems the fight went on too long child. You are exhausted. Best to flee with what you managed to gain!');

            $character = $this->character->refresh();

            $this->rewardPlayer($character, $delveAutomation->refresh());

            $this->deletePackCache();

            event(new UpdateCharacterStatus($character));

            event(new AutomationTimeOut($character->user, 0));
        } catch (MissingInventoryException $exception) {
            $this->character->user()->update(['will_be_deleted' => true]);
            Log::warning('Delve exploration stopped for a character with missing inventory.', [
                'job' => self::class,
                'character_id' => $this->character->id,
                'user_id' => $this->character->user_id,
                'automation_id' => $this->automationId,
                'delve_exploration_id' => $this->delveAutomationId,
                'exception' => $exception,
            ]);

            return;
        }
    }

    /**
     * Delete the cached pack fight data for the current monster.
     *
     * @return void This method does not return a value.
     */
    private function deletePackCache(): void
    {
        Cache::delete('delve-monster-'.$this->character->id.'-'.$this->monster->id.'-fight');
    }

    /**
     * Select and store the next monster to fight for the Delve automation.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @return void This method does not return a value.
     */
    private function updateMonsterForNextFight(DelveExplorationModel $delveExploration): void
    {
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
            'monster_id' => $monsterId,
        ]);
    }

    /**
     * Persist the given attributes onto the Delve automation record.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param array $data The attributes to persist.
     * @return void This method does not return a value.
     */
    private function updateDelveAutomation(DelveExplorationModel $delveExploration, array $data): void
    {
        $delveExploration->update($data);
    }

    /**
     * Handle an encounter.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param array $params The encounter parameters.
     * @param int $timeDelay The delay, in minutes, before the next round.
     * @return bool True when the encounter was survived.
     *
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
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param array $params The fight parameters.
     * @return bool True when the fight was survived.
     *
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

    /**
     * Fight through the entire monster pack for a Delve round, accumulating rewards.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param array $params The fight parameters.
     * @return bool True when the entire pack was survived.
     */
    private function fightMultipleEnemies(DelveExplorationModel $delveExploration, array $params): bool
    {
        $totalXpToReward = 0;
        $totalSkillXpToReward = 0;
        $characterRewardService = $this->characterRewardService->setCharacter($this->character);
        $characterSkillService = $this->skillService->setSkillInTraining($this->character);

        $packSize = $params['pack_size'];

        for ($i = 1; $i <= $packSize; $i++) {
            $this->attempts = 0;
            $survived = $this->fightAutomationMonster($delveExploration, $params);

            if (! $survived) {
                return false;
            }

            $params['selected_monster_id'] = $this->monster?->id ?? $params['selected_monster_id'];

            $totalXpToReward += $characterRewardService->fetchXpForMonster($this->monster);
            $totalSkillXpToReward += $characterSkillService->getXpForSkillIntraining($this->character, $this->monster->xp);
        }

        $this->battleData = [
            'total_creatures' => $params['pack_size'],
            'total_xp' => $this->getPackSizeXp($packSize, $totalXpToReward),
            'total_faction_points' => 0,
            'total_skill_xp' => $this->getPackSizeXp($packSize, $totalSkillXpToReward),
        ];

        return true;
    }

    /**
     * Apply the pack-size xp bonus multiplier to a base xp amount.
     *
     * @param int $packSize The monster pack size.
     * @param int $xp The base xp amount.
     * @return int The xp amount after the pack-size bonus.
     */
    private function getPackSizeXp(int $packSize, int $xp): int
    {
        return match ($packSize) {
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
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param array $params The fight parameters.
     * @return bool True when the fight was survived.
     *
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

        $this->battleData = [
            'total_faction_points' => 0,
        ];

        return true;
    }

    /**
     * Send the flavor log messages that introduce the current Delve encounter.
     *
     * @param float $increaseAmount The enemy strength increase applied.
     * @param string $monsterName The current monster's name.
     * @param int $packSize The monster pack size.
     * @return void This method does not return a value.
     */
    private function showEverBurningMessages(float $increaseAmount, string $monsterName, int $packSize): void
    {

        if ($packSize > 1 && $this->showedEncounterMessage) {
            return;
        }

        $this->sendOutEventLogUpdate('The Ever Burning Candle erupts forward and the light illuminates the foul beast: '.$monsterName);

        if ($packSize > 1) {
            $this->sendOutEventLogUpdate('Holy shit child, there are '.$packSize.' of them. Hold your ground!');
        }

        if ($increaseAmount > 0) {
            $percent = $increaseAmount * 100;

            $this->sendOutEventLogUpdate('The beast(s) is radiant with magic, you know its strength has increased by: '.$percent.'%');
        }

        $this->showedEncounterMessage = true;
    }

    /**
     * Handle when a character dies in automation.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param array $data The fight result data.
     * @return bool True when the character died and the Delve was ended.
     *
     * @throws Exception
     */
    private function handleWhenCharacterDies(DelveExplorationModel $delveExploration, array $data): bool
    {

        if ($data['health']['current_character_health'] <= 0) {

            $this->createDelveLog($delveExploration, DelveOutcome::DIED, $data);

            $delveExploration->update([
                'completed_at' => now(),
                'ended_reason' => DelveOutcome::DIED->value,
                'panel_dismissed_at' => null,
            ]);

            event(new DelveStatusUpdated($this->character->user->id));

            CharacterAutomation::where('character_id', $delveExploration->character_id)->where('type', AutomationType::DELVE->value)->delete();

            $this->sendOutEventLogUpdate('You died during the delve. Exploration has ended, but not all is lost, you awaken from your wounds there might be treasures waiting, treasures you collected. (See server messages for treasures)');

            $this->rewardPlayer($this->character, $delveExploration->refresh());

            $this->deletePackCache();

            event(new AutomationTimeOut($this->character->user, 0));

            return true;
        }

        return false;
    }

    /**
     * Determine whether the fight should continue based on the character's and monster's health.
     *
     * @param array $data The fight result data.
     * @return bool True when another attack should be attempted.
     */
    private function shouldAttackAgain(array $data): bool
    {

        if ($data['health']['current_character_health'] <= 0) {
            return false;
        }

        if ($data['health']['current_monster_health'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Should we bail?
     *
     * @param CharacterAutomation|null $automation The character's Delve automation record, if any.
     * @param DelveExplorationModel|null $delveExploration The Delve exploration record, if any.
     * @return bool True when the job should bail without fighting.
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

        if (! is_null($delveExploration->completed_at)) {
            return true;
        }

        return false;
    }

    /**
     * End automation.
     *
     * @param CharacterAutomation|null $automation The character's Delve automation record, if any.
     * @param DelveExplorationModel|null $delveExploration The Delve exploration record, if any.
     * @param CharacterCacheData $characterCacheData The character cache data service.
     * @return void This method does not return a value.
     *
     * @throws Exception
     */
    private function endAutomation(?CharacterAutomation $automation, ?DelveExplorationModel $delveExploration, CharacterCacheData $characterCacheData): void
    {
        $characterCacheData->deleteCharacterSheet($this->character);

        if (! is_null($automation)) {
            $automation->delete();

            $character = $this->character->refresh();

            event(new UpdateCharacterStatus($character));

            event(new AutomationTimeOut($character->user, 0));
        }

        if (! is_null($delveExploration)) {

            if (! is_null($delveExploration->completed_at)) {
                return;
            }

            $delveExploration->update([
                'completed_at' => now(),
                'ended_reason' => 'natural_end',
                'panel_dismissed_at' => null,
            ]);

            event(new DelveStatusUpdated($this->character->user->id));

            $this->sendOutEventLogUpdate('You climb from the depths of the delve exploration, covered in blood, grime, dirt. Carrying the treasures you went searching for. Maybe now you have more answers about the darkness, or maybe you have more trauma.', true);

            $this->sendOutEventLogUpdate('Your adventure is over child. Now is the time to rest, relax, heal and sort through your haul to weed out the worthless.', true);

            $this->rewardPlayer($character, $delveExploration->refresh());
        }

        if (is_null($delveExploration) || is_null($automation)) {
            $this->character->currentAutomations()->delete();
        }
    }

    /**
     * Fight the monster.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @return array The fight result data.
     *
     * @throws InvalidArgumentException
     */
    private function fightMonster(DelveExplorationModel $delveExploration): array
    {
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

    /**
     * Record a Delve round outcome log and broadcast the updated Delve status.
     *
     * @param DelveExplorationModel $delveExploration The Delve exploration record.
     * @param DelveOutcome $outcome The round outcome.
     * @param array $fightData The fight result data.
     * @return void This method does not return a value.
     */
    private function createDelveLog(DelveExplorationModel $delveExploration, DelveOutcome $outcome, array $fightData): void
    {
        $delveExploration->delveLogs()->create([
            'character_id' => $this->character->id,
            'increased_enemy_strength' => $delveExploration->increase_enemy_strength,
            'delve_exploration_id' => $delveExploration->id,
            'pack_size' => $this->packSize,
            'outcome' => $outcome->value,
            'fight_data' => $fightData,
        ]);
        event(new DelveMonitoringUpdated($this->character->id));
        event(new DelveStatusUpdated($this->character->user->id));
        $this->broadcastTopsUpdateService->broadcastDelveCurrentMonth();
    }

    /**
     * Send out event log updates
     *
     * @param string $message The log message text.
     * @param bool $makeItalic Whether the message should render italicized.
     * @param bool $isReward Whether the message represents a reward.
     * @return void This method does not return a value.
     */
    private function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void
    {
        if ($this->character->isLoggedIn()) {
            event(new AutomationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
        }
    }

    /**
     * Send a server message to the character about a Delve reward drop.
     *
     * @param string $message The server message text.
     * @param int $itemId The rewarded item's inventory slot id.
     * @return void This method does not return a value.
     */
    private function sendServerMessage(string $message, int $itemId): void
    {

        if ($this->character->isLoggedIn()) {
            event(new ServerMessageEvent($this->character->user, $message, $itemId));
        }
    }

    /**
     * Reward the player for automation completion.
     *
     * @param Character $character The character to reward.
     * @param DelveExplorationModel $delveExploration The completed Delve exploration record.
     * @return void This method does not return a value.
     *
     * @throws Exception
     */
    private function rewardPlayer(Character $character, DelveExplorationModel $delveExploration): void
    {
        $this->characterRewardService->setCharacter($character);

        $start = $delveExploration->started_at;
        $end = $delveExploration->completed_at;

        $timeElapsedInHours = $start->diffInHours($end);

        $cosmicItem = null;
        $mythicItem = null;
        $uniqueItem = null;

        if ($timeElapsedInHours > 6) {
            $cosmicItem = $this->characterRewardService->getSpecialGearDrop(RandomAffixTier::COSMIC->value);
        }

        if ($timeElapsedInHours > 4 && $timeElapsedInHours < 6) {
            $mythicItem = $this->characterRewardService->getSpecialGearDrop(RandomAffixTier::MYTHIC->value);
        }

        if ($timeElapsedInHours > 2) {
            $uniqueItem = $this->characterRewardService->getSpecialGearDrop(RandomAffixTier::LEGENDARY->value);
        }

        $goldReward = 0;

        if (! is_null($cosmicItem)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $cosmicItem->id,
            ]);

            $goldReward += 1_000_000_000_000;

            $this->sendOutEventLogUpdate('Gained one trillion gold for completing the delve.', false, true);

            $this->sendOutEventLogUpdate('Gained a cosmic item child! (Check Server Messages).', false, true);

            $this->sendServerMessage('You were rewarded with a cosmic item: '.$cosmicItem->affix_name.' for surviving for more then 6 hours in a delve!', $slot->id);
        }

        if (! is_null($mythicItem)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $mythicItem->id,
            ]);

            $goldReward += 1_000_000_000;

            $this->sendOutEventLogUpdate('Gained one billion gold for completing the delve.', false, true);

            $this->sendOutEventLogUpdate('Gained a mythic item child! (Check Server Messages).', false, true);

            $this->sendServerMessage('You were rewarded with a mythic item: '.$mythicItem->affix_name.' for surviving for more then 4 hours in a delve!', $slot->id);
        }

        if (! is_null($uniqueItem)) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $uniqueItem->id,
            ]);

            $goldReward += 1_000_000;

            $this->sendOutEventLogUpdate('Gained one million gold for completing the delve.', false, true);

            $this->sendOutEventLogUpdate('Gained a unique item child! (Check Server Messages).', false, true);

            $this->sendServerMessage('You were rewarded with a unique item: '.$uniqueItem->affix_name.' for surviving for more then 2 hours in a delve!', $slot->id);
        }

        if ($goldReward === 0) {
            $goldReward = 1_000;

            $this->sendOutEventLogUpdate('Gained one thousand gold for completing the delve.', false, true);
        }

        $gold = $character->gold + $goldReward;

        if ($gold >= CurrencyLimit::MAX_GOLD) {
            $gold = CurrencyLimit::MAX_GOLD;
        }

        $character->update(['gold' => $gold]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));
    }

    // @codeCoverageIgnoreStart
    /**
     * Handle the job's terminal queue failure by reporting and finalizing the Delve automation.
     *
     * @param Throwable $throwable The exception that failed the job.
     * @return void This method does not return a value.
     */
    public function failed(Throwable $throwable): void
    {
        $context = [
            'character_id' => $this->character?->id,
            'automation_id' => $this->automationId,
            'delve_automation_id' => $this->delveAutomationId,
            'exception_class' => $throwable::class,
            'exception_message' => $throwable->getMessage(),
        ];

        Log::error('Delve exploration job failed.', $context);

        (new MonitoredBugReportService)->reportError(
            'delve-exploration',
            $throwable->getMessage(),
            ['character_id' => $this->character?->id, 'automation_id' => $this->automationId],
            $throwable::class,
            $this->character?->id,
        );

        $automation = CharacterAutomation::where('id', $this->automationId)->first();
        $delveAutomation = DelveExplorationModel::where('id', $this->delveAutomationId)->first();

        if (! is_null($delveAutomation) && is_null($delveAutomation->completed_at)) {
            $delveAutomation->update([
                'completed_at' => now(),
                'ended_reason' => DelveOutcome::ERROR->value,
                'panel_dismissed_at' => null,
            ]);
        }

        if (! is_null($automation)) {
            $automation->delete();
        }

        if (! is_null($this->character)) {
            event(new DelveMonitoringUpdated($this->character->id));
        }
    }
    // @codeCoverageIgnoreEnd
}
