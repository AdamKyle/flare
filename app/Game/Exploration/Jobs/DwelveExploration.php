<?php

namespace App\Game\Exploration\Jobs;

use App\Flare\Models\DwelveExploration as DwelveExplorationModel;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
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

class DwelveExploration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const MAX_ATTEMPTS = 10;

    public Character $character;

    private CharacterRewardService $characterRewardService;

    private SkillService $skillService;

    private MonsterFightService $monsterFightService;

    private FactionHandler $factionHandler;

    private ?Monster $monster = null;

    private int $automationId;

    private int $dwelveAutomationId;

    private string $attackType;

    private int $timeDelay;

    private int $attempts = 0;

    public function __construct(Character $character, int $automationId, int $dwelveExplorationId, string $attackType, int $timeDelay)
    {
        $this->character = $character;
        $this->automationId = $automationId;
        $this->dwelveAutomationId = $dwelveExplorationId;
        $this->attackType = $attackType;
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

        $dwelveAutomation = DwelveExplorationModel::where('character_id', $this->character->id)->where('id', $this->dwelveAutomationId)->first();

        if ($this->shouldBail($automation, $dwelveAutomation)) {
            $this->endAutomation($automation, $dwelveAutomation, $characterCacheData);

            Cache::delete('can-character-survive-' . $this->character->id);

            return;
        }

        $params = [
            'selected_monster_id' => $dwelveAutomation->monster_id,
            'attack_type' => $this->attackType,
        ];

        if ($this->encounter($dwelveAutomation, $params, $this->timeDelay)) {

            $time = now()->diffInMinutes($automation->completed_at);

            $delay = $time >= $this->timeDelay ? $this->timeDelay : ($time > 1 ? $time : 0);

            if ($delay === 0) {
                $this->endAutomation($automation, $dwelveAutomation, $characterCacheData);

                return;
            }

            $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id']);

            $this->updateDwelveAutomation($dwelveAutomation, [
                'increase_enemy_strength' => $dwelveAutomation->increase_enemy_strength + 0.05
            ]);

            DwelveExploration::dispatch($this->character, $this->automationId, $this->attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');

            return;
        }

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $automation->delete();

            $dwelveAutomation->update([
                'completed_at' => now(),
            ]);

            $this->sendOutEventLogUpdate('Seems the fight went on too long child. You are exhausted. Best to flee with what you managed to gain!');

            $character = $this->character->refresh();

            $this->rewardPlayer($character);

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));

            return;
        }

        $automation->delete();

        $dwelveAutomation->update([
            'completed_at' => now(),
        ]);

        $this->sendOutEventLogUpdate('Something went wrong with dwelve. Could not process fight. Dwelve Canceled.');

        event(new ExplorationTimeOut($this->character->user, 0));
    }

    private function updateDwelveAutomation(DwelveExplorationModel $dwelveExploration, array $data): void {
        $dwelveExploration->update($data);
    }

    /**
     * Handle an encounter.
     *
     * @param DwelveExplorationModel $dwelveExploration
     * @param array $params
     * @param int $timeDelay
     * @return bool
     * @throws InvalidArgumentException
     */
    private function encounter(DwelveExplorationModel $dwelveExploration, array $params, int $timeDelay): bool
    {

        $canSurviveFights = $this->canSurviveFight($dwelveExploration, $params);

        if ($canSurviveFights) {

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
     * @param DwelveExplorationModel $dwelveExploration
     * @param array $params
     * @return bool
     * @throws InvalidArgumentException
     */
    private function canSurviveFight(DwelveExplorationModel $dwelveExploration, array $params): bool
    {

        $this->sendOutEventLogUpdate('Before you in the darkness, lies a beast unknown to man. Kill it child. Slaughter it!');

        return $this->fightAutomationMonster($dwelveExploration, $params);

    }

    /**
     * Fight monster through automation.
     *
     * @param DwelveExplorationModel $dwelveExploration
     * @param array $params
     * @return bool
     * @throws InvalidArgumentException
     */
    private function fightAutomationMonster(DwelveExplorationModel $dwelveExploration, array $params): bool
    {

        $data = $this->monsterFightService->setupMonster($this->character, $params, true, true);

        $monsterName = $this->monsterFightService->getMonster()->name;

        $increaseAmount = $dwelveExploration->increase_enemy_strength;

        $this->sendOutEventLogUpdate('The Ever Burning Candle erupts forward and the light illuminates the foul beast: ' . $monsterName);

        if ($increaseAmount > 0) {
            $this->sendOutEventLogUpdate("The beast is radiant with magic, you know its strength has increased by " . number_format($increaseAmount) . '%');
        }

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($dwelveExploration, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        $data = $this->fightMonster($dwelveExploration);

        $battleMessages = $dwelveExploration->battle_messages;
        $battleMessages[] = $data;

        if (empty($data)) {
            return false;
        }

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($dwelveExploration, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        return true;
    }

    /**
     * Handle when a character dies in automation.
     *
     * @param DwelveExplorationModel $dwelveExploration
     * @param array $data
     * @return bool
     */
    private function handleWhenCharacterDies(DwelveExplorationModel $dwelveExploration, array $data): bool {
        if ($data['health']['current_character_health'] <= 0) {

            $dwelveExploration->update([
                'completed_at' => now(),
            ]);

            CharacterAutomation::where('character_id', $dwelveExploration->character_id)->where('type', AutomationType::DWELVE)->delete();

            $this->sendOutEventLogUpdate('You died during the dwelve. Exploration has ended, but not all is lost, you awaken from your wounds there might be treasures waiting, treasures you collected. (See server messages for treasures)');

            $this->rewardPlayer($this->character);

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
     * @param DwelveExplorationModel|null $dwelveExploration
     * @return bool
     */
    private function shouldBail(?CharacterAutomation $automation = null, ?DwelveExplorationModel $dwelveExploration = null): bool
    {

        if (is_null($automation)) {
            return true;
        }

        if (is_null($dwelveExploration)) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        if (!is_null($dwelveExploration->completed_at)) {
            return true;
        }

        return false;
    }

    /**
     * End automation.
     *
     * @param CharacterAutomation|null $automation
     * @param DwelveExplorationModel|null $dwelveExploration
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function endAutomation(?CharacterAutomation $automation, ?DwelveExplorationModel $dwelveExploration, CharacterCacheData $characterCacheData): void
    {
        $characterCacheData->deleteCharacterSheet($this->character);

        if (! is_null($automation)) {
            $automation->delete();

            $character = $this->character->refresh();

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));
        }

        if (! is_null($dwelveExploration)) {

            if (!is_null($dwelveExploration->completed_at)) {
                return;
            }

            $dwelveExploration->update([
                'completed_at' => now(),
            ]);

            $this->sendOutEventLogUpdate('You climb from the depths of the dwelve exploration, covered in blood, grime, dirt. Carrying the treasures you went searching for. Maybe now you have more answers about the darkness, or maybe you have more trauma.', true);

            $this->sendOutEventLogUpdate('Your adventure is over child. Now is the time to rest, relax, heal and sort through your haul to weed out the worthless.', true);

            $this->rewardPlayer($character);
        }
    }

    /**
     * Fight the monster.
     *
     * @param DwelveExplorationModel $dwelveExploration
     * @return array
     * @throws InvalidArgumentException
     */
    private function fightMonster(DwelveExplorationModel $dwelveExploration): array {
        $data = $this->monsterFightService->fightMonster($this->character, $this->attackType, false, true);

        $battleMessages = $dwelveExploration->battle_messages;
        $battleMessages[] = $data;

        $this->updateDwelveAutomation($dwelveExploration, [
            'battle_messages' => $battleMessages
        ]);

        if ($this->shouldAttackAgain($data) && $this->attempts >= self::MAX_ATTEMPTS) {
            $this->sendOutEventLogUpdate('Seems this beast is a little stronger then normal. You swing again and lash out your magics.', true);

            return [];
        }

        if ($this->shouldAttackAgain($data) && $this->attempts < self::MAX_ATTEMPTS) {
            $this->attempts++;

            return $this->fightMonster();
        }

        return $data;
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

    /**
     * Reward the player for automation completion.
     *
     * @param Character $character
     * @return void
     */
    private function rewardPlayer(Character $character): void
    {

        $gold = $character->gold + 10_000;

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update(['gold' => $gold]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));

        $this->sendOutEventLogUpdate('Gained 10k Gold for completing the exploration.', false, true);
    }
}
