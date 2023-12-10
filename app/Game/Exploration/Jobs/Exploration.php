<?php

namespace App\Game\Exploration\Jobs;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Maps\Events\UpdateDuelAtPosition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class Exploration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * @var int $automationId
     */
    private int $automationId;

    /**
     * @var string $attackType
     */
    private string $attackType;

    /**
     * @var int $timeDelay
     */
    private int $timeDelay;


    /**
     * @param Character $character
     * @param int $automationId
     * @param string $attackType
     * @param int $timeDelay
     */
    public function __construct(Character $character, int $automationId, string $attackType, int $timeDelay) {
        $this->character    = $character;
        $this->automationId = $automationId;
        $this->attackType   = $attackType;
        $this->timeDelay    = $timeDelay;
    }

    /**
     * @param MonsterPlayerFight $monsterPlayerFight
     * @param BattleEventHandler $battleEventHandler
     * @param FactionHandler $factionHandler
     * @param GuideQuestService $guideQuestService
     * @param CharacterCacheData $characterCacheData
     * @return void
     * @throws \Exception
     */
    public function handle(MonsterPlayerFight $monsterPlayerFight, BattleEventHandler $battleEventHandler, FactionHandler $factionHandler, GuideQuestService $guideQuestService, CharacterCacheData $characterCacheData): void {

        $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

        if ($this->shouldBail($automation)) {
            $this->endAutomation($automation, $characterCacheData);

            event(new UpdateDuelAtPosition($this->character->user));

            Cache::delete('can-character-survive-' . $this->character->id);

            return;
        }

        $automation = $this->updateAutomation($automation);

        $params = [
            'selected_monster_id' => $automation->monster_id,
            'attack_type'         => $this->attackType,
        ];

        $response = $monsterPlayerFight->setUpFight($this->character, $params);

        if ($response instanceof MonsterPlayerFight) {

            if ($this->encounter($response, $automation, $battleEventHandler, $params, $this->timeDelay)) {

                $this->rewardAdditionalFactionPoints($factionHandler, $guideQuestService);

                $time = now()->diffInMinutes($automation->completed_at);

                $delay = $time >= $this->timeDelay ? $this->timeDelay : ($time > 1 ? $time : 0);

                if ($delay === 0) {
                    $this->endAutomation($automation, $characterCacheData);

                    event(new UpdateDuelAtPosition($this->character->user));

                    return;
                }

                Exploration::dispatch($this->character, $this->automationId, $this->attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');
            }

            $response->deleteCharacterCache($this->character);

            return;
        }

        $automation->delete();

        event(new UpdateDuelAtPosition($this->character->user));

        $this->sendOutEventLogUpdate('Something went wrong with automation. Could not process fight. Automation Canceled.');

        event(new ExplorationTimeOut($this->character->user, 0));
    }

    protected function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void {
        if ($this->character->isLoggedIn()) {
            event(new ExplorationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
        }
    }

    /**
     * Handle the encounter.
     *
     * @param MonsterPlayerFight $response
     * @param CharacterAutomation $automation
     * @param BattleEventHandler $battleEventHandler
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    protected function encounter(MonsterPlayerFight $response, CharacterAutomation $automation, BattleEventHandler $battleEventHandler, array $params, int $timeDelay): bool {

        $canSurviveFights = $this->canSurviveFight($response, $automation, $battleEventHandler, $params);

        if ($canSurviveFights) {

            $this->sendOutEventLogUpdate('You and The Guide search the area looking for any other signs of them. That\'s when The Guide spots them and points', true);

            $enemies = rand(10, 25);

            $this->sendOutEventLogUpdate('"Chirst, child there are: '.$enemies.' of them ..."
            The Guide hisses at you from the shadows. You ignore his words and prepare for battle. One right after the other ...', true);

            for ($i = 1; $i <= $enemies; $i++) {
                $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id']);
            }

            $this->sendOutEventLogUpdate('The last of the enemies fall. Covered in blood, exhausted, you look around for any signs of more of their friends. The area is silent. "Another day, another battle.
            We managed to survive." The Guide states as he walks from the shadows. The pair of you set off in search of the next adventure ...
            (Exploration will begin again in '.$timeDelay.' minutes)', true);

            return true;
        }

        return false;
    }

    protected function canSurviveFight(MonsterPlayerFight $response, CharacterAutomation $automation, BattleEventHandler $battleEventHandler, array $params): bool {

        if (Cache::has('character-can-survive-' . $this->character->id)) {
            return true;
        }

        $this->sendOutEventLogUpdate('"Child, I can see a small group of these creature. If we slaughter them we might learn something." The guide insists. "Theres ten of them. Quick, kill them. We will continue the hunt!"');

        for ($i = 1; $i <= 10; $i++) {
            if (!$this->fightAutomationMonster($response, $automation, $battleEventHandler, $params)) {
                return false;
            }
        }

        Cache::put('can-character-survive-' . $this->character->id, true);

        return true;
    }

    /**
     * Fight the monster in automation.
     *
     * @param MonsterPlayerFight $response
     * @param CharacterAutomation $automation
     * @param BattleEventHandler $battleEventHandler
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    protected function fightAutomationMonster(MonsterPlayerFight $response, CharacterAutomation $automation, BattleEventHandler $battleEventHandler, array $params): bool {
        $fightResponse = $response->fightMonster();

        if (!$fightResponse) {
            $automation->delete();

            $battleEventHandler->processDeadCharacter($this->character);

            $response->deleteCharacterCache($this->character);

            $this->sendOutEventLogUpdate('You died during exploration. Exploration has ended.');

            event(new ExplorationTimeOut($this->character->user, 0));

            return false;
        }

        $response->resetBattleMessages();

        $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id']);

        return true;
    }

    /**
     * Should we bail on the automation?
     *
     * @param CharacterAutomation|null $automation
     * @return bool
     */
    protected function shouldBail(CharacterAutomation $automation = null): bool {

        if (is_null($automation)) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        return false;
    }

    /**
     * Update the automation.
     *
     * @param CharacterAutomation $automation
     * @return CharacterAutomation
     */
    protected function updateAutomation(CharacterAutomation $automation): CharacterAutomation {
        if (!is_null($automation->move_down_monster_list_every)) {
            $characterLevel = $this->character->refresh()->level;

            $automation->update([
                'current_level' => $characterLevel,
            ]);

            $automation = $automation->refresh();


            if (($automation->current_level - $automation->previous_level) >= $automation->move_down_monster_list_every) {
                $monster = Monster::find($automation->monster_id);

                $nextMonster = Monster::where('id', '>', $monster->id)->orderBy('id','asc')->first();

                if (!is_null($nextMonster)) {
                    $automation->update([
                        'monster_id'     => $nextMonster->id,
                        'previous_level' => $characterLevel,
                    ]);
                }
            }
        }

        return $automation->refresh();
    }

    /**
     * Reward the faction points.
     *
     * @param FactionHandler $factionHandler
     * @param GuideQuestService $guideQuestService
     * @return void
     */
    protected function rewardAdditionalFactionPoints(FactionHandler $factionHandler, GuideQuestService $guideQuestService): void {
        $map     = GameMap::find($this->character->map->game_map_id);
        $faction = Faction::where('character_id', $this->character->id)->where('game_map_id', $map->id)->first();

        if (is_null($faction)) {
            return;
        }

        if ($faction->maxed) {
            return;
        }

        $hasQuestItem = $factionHandler->playerHasQuestItem($this->character);

        if ($faction->current_level == 0) {
            $amount = 50;
        } else if ($faction->current_level === 0 && $hasQuestItem) {
            $amount = 25;
        } else {
            $amount = 10;
        }

        $this->sendOutEventLogUpdate('Gained: ' . $amount . ' Additional ' . $map->name . ' Faction points', false, true);

        $factionHandler->handleCustomFactionAmount($this->character, $amount);
    }

    /**
     * End the automation.
     *
     * @param CharacterAutomation|null $automation
     * @return void
     */
    protected function endAutomation(?CharacterAutomation $automation, CharacterCacheData $characterCacheData): void {
        if (!is_null($automation)) {
            $automation->delete();

            $characterCacheData->deleteCharacterSheet($this->character);

            $this->sendOutEventLogUpdate('"Phew, child! I did not think we would survive all of your shenanigans.
            So many times I could have died! Do you ever think about anyone other than yourself? No? Didn\'t think so." The Guide storms off and you follow him in silence.', true);

            $this->sendOutEventLogUpdate('Your adventures over, you head to back to the nearest town. Upon arriving, you and The Guide spot the closest Inn. Soaked in the
            blood of your enemies, the sweat of the lingers on you like a bad smell. Entering the establishment and finding a table, you are greeted by a big busty women with shaggy long red hair messily tied in a pony tail.
            She leans down to the table, her cleavage close enough to your face that you can see the freckles and lines of age. Her grin missing a tooth, she states: "What can I get the both of ya?" You shutter on the inside.', true);

            $character = $this->character->refresh();

            $this->rewardPlayer($character);

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));
        }
    }

    /**
     * Reward the player.
     *
     * @param Character $character
     * @return void
     */
    protected function rewardPlayer(Character $character): void {

        $gold = $character->gold + 10000;

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update(['gold' => $gold]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));

        $this->sendOutEventLogUpdate('Gained 10k Gold for completing the exploration.', false, true);
    }
}
