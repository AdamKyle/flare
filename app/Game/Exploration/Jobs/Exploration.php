<?php

namespace App\Game\Exploration\Jobs;

use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Monster;
use App\Game\Exploration\Events\ExplorationDetails;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Events\UpdateAutomationsList;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Handlers\RewardHandler;
use App\Game\Exploration\Services\EncounterService;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\UserOnlineValue;
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
            //$this->endAutomation($rewardHandler, $automation);

            return;
        }

        $automation = $this->updateAutomation($automation);

        $params = [
            'selected_monster_id' => $automation->monster_id,
            'attack_type'         => $this->attackType,
        ];

        $response = $monsterPlayerFight->setUpFight($this->character, $params);

        if ($response instanceof MonsterPlayerFight) {

            $fightResponse = $response->fightMonster();

            $response->deleteCharacterCache($this->character);

            if (!$fightResponse) {
                $battleEventHandler->processDeadCharacter($this->character);

                $automation->delete();

                event(new ExplorationLogUpdate($this->character->user, 'You died during exploration. Exploration has ended.'));

                event(new ExplorationTimeOut($this->character->user, 0));

                return;
            }

            $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id'], true);
        }
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

    protected function endAutomation(RewardHandler $rewardHandler, ?CharacterAutomation $automation) {
        if (!is_null($automation)) {
            $automation->delete();

            event(new ExplorationLogUpdate($this->character->user, 'Phew, child! I did not think we would survive all of your shenanigans.
                So many times I could have died! Do you ever think about anyone other than yourself? No? Didn\'t think so. Either way, I am off.
                Let me know when we go on our next adventure.', true));

            $rewardHandler->processRewardsForExplorationComplete($this->character);

            event(new ExplorationLogUpdate($this->character->user, 'Exploration is now over, rewards may still be processing and will be with you shortly.'));

            $character = $this->character->refresh();

            event(new ExplorationTimeOut($character->user, 0));
        }
    }
}
