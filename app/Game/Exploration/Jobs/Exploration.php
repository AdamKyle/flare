<?php

namespace App\Game\Exploration\Jobs;

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

    public $character;

    private $automationId;

    private $attackType;

    public function __construct(Character $character, int $automationId, string $attackType) {
        $this->character    = $character;
        $this->automationId = $automationId;
        $this->attackType   = $attackType;
    }

    public function handle(EncounterService $encounterService, ExplorationAutomationService $explorationAutomationService, RewardHandler $rewardHandler) {

        $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

        if ($this->shouldBail($automation)) {
            $this->endAutomation($rewardHandler, $automation);

            return;
        }

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

                    $data = $explorationAutomationService->fetchData($this->character, $automation->refresh());

                    event(new ExplorationDetails($this->character->user, $data));
                }
            }
        }

        $succeeded = $encounterService->processEncounter($this->character, $automation);

        if ($succeeded) {
            $timeLeft = now()->diffInMinutes($automation->completed_at);

            if ($timeLeft < 10) {
                $this->endAutomation($rewardHandler, $automation);

                return;
            }

            $character = $this->character->refresh();

            event(new ExplorationLogUpdate($character->user, 'Next encounter will start in 10 minutes.'));

            Exploration::dispatch($character, $automation->id, $this->attackType)->delay(now()->addMinutes(10));
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
            event(new ExplorationStatus($character->user, false));
            event(new UpdateTopBarEvent($character));
            event(new UpdateAutomationsList($character->user, $character->currentAutomations));
        }
    }
}
