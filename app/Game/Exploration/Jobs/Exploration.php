<?php

namespace App\Game\Exploration\Jobs;

use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Monster;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Handlers\RewardHandler;
use App\Game\Exploration\Services\EncounterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Character;

class Exploration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Character $character;

    private int $automationId;

    private string $attackType;


    public function __construct(Character $character, int $automationId, string $attackType) {
        $this->character    = $character;
        $this->automationId = $automationId;
        $this->attackType   = $attackType;
    }

    public function handle(MonsterPlayerFight $monsterPlayerFight, BattleEventHandler $battleEventHandler) {

        $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

        if ($this->shouldBail($automation)) {
            $this->endAutomation($automation);

            return;
        }

        $automation = $this->updateAutomation($automation);

        $params = [
            'selected_monster_id' => $automation->monster_id,
            'attack_type'         => $this->attackType,
        ];

        $response = $monsterPlayerFight->setUpFight($this->character, $params);

        if ($response instanceof MonsterPlayerFight) {

            if ($this->encounter($response, $automation, $battleEventHandler, $params)) {
                Exploration::dispatch($this->character, $this->automationId, $this->attackType)->delay(now()->addMinutes(5));
            }
        }

        $automation->delete();

        event(new ExplorationLogUpdate($this->character->user, 'Something went wrong with automation. Could not process fight. Automation Canceled.'));

        event(new ExplorationTimeOut($this->character->user, 0));
    }

    protected function encounter(MonsterPlayerFight $response, CharacterAutomation $automation, BattleEventHandler $battleEventHandler, array $params) {
        $user = $this->character->user;

        event(new ExplorationLogUpdate($user, 'While on your exploration of the area, you encounter a: ' . $response->getEnemyName()));

        if ($this->fightAutomationMonster($response, $automation, $battleEventHandler, $params)) {
            event(new ExplorationLogUpdate($user, 'You search the corpse of you enemy for clues, where did they come from? None to be found. Upon searching the area further, you find the enemies friends.', true));

            $enemies = rand(1, 6);

            event(new ExplorationLogUpdate($user, '"Chirst child there are: '.$enemies.' of them ..."
            The guides hisses at you from the shadows. You ignore his words and prepare for battle. One right after the other ...', true));

            for ($i = 1; $i <= $enemies; $i++) {
                if (!$this->fightAutomationMonster($response, $automation, $battleEventHandler, $params)) {
                    return false;
                }
            }

            event(new ExplorationLogUpdate($user, 'The last of the enemies fall. Covered in blood, exhausted, you look around for any signs of more of their friends. The area is silent. "Another day, another battle.
            We managed to survive." The Guide states as he walks from the shadows. The pair of you set off in search of the next adventure ...
            (Exploration will begin again in 5 minutes)', true));

            return true;
        }

        return false;
    }

    protected function fightAutomationMonster(MonsterPlayerFight $response, CharacterAutomation $automation, BattleEventHandler $battleEventHandler, array $params) {
        $fightResponse = $response->fightMonster();

        $response->deleteCharacterCache($this->character);

        if (!$fightResponse) {
            $battleEventHandler->processDeadCharacter($this->character);

            $automation->delete();

            event(new ExplorationLogUpdate($this->character->user, 'You died during exploration. Exploration has ended.'));

            event(new ExplorationTimeOut($this->character->user, 0));

            return false;
        }

        $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id'], true);

        return true;
    }

    protected function shouldBail(CharacterAutomation $automation = null): bool {

        if (is_null($automation)) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        return false;
    }

    protected function updateAutomation(CharacterAutomation $automation) {
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
    }

    protected function endAutomation(?CharacterAutomation $automation) {
        if (!is_null($automation)) {
            $automation->delete();

            event(new ExplorationLogUpdate($this->character->user, 'Phew, child! I did not think we would survive all of your shenanigans.
                So many times I could have died! Do you ever think about anyone other than yourself? No? Didn\'t think so. Either way, I am off.
                Let me know when we go on our next adventure.', true));

            $character = $this->character->refresh();

            $this->rewardPlayer($character);

            event(new ExplorationTimeOut($character->user, 0));
        }
    }

    protected function rewardPlayer(Character $character) {

        $gold = $character->gold + 10000;

        if ($gold > MaxCurrenciesValue::GOLD) {
            $gold = MaxCurrenciesValue::GOLD;
        }

        $character->update(['gold' => $gold]);

        event(new UpdateTopBarEvent($character->refresh()));

        event(new ExplorationLogUpdate($character->user, 'Gained 10k Gold for completing the exploration.', false, true));
    }
}
