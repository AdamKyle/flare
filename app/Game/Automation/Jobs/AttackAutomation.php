<?php

namespace App\Game\Automation\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Monster;
use App\Game\Automation\Events\AutomatedAttackDetails;
use App\Game\Automation\Events\AutomatedAttackStatus;
use App\Game\Automation\Events\AutomationAttackTimeOut;
use App\Game\Automation\Services\AttackAutomationService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\UserOnlineValue;
use App\Flare\Models\Character;
use App\Game\Automation\Services\ProcessAttackAutomation;

class AttackAutomation implements ShouldQueue
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

    public function handle(ProcessAttackAutomation $processAttackAutomation, AttackAutomationService $attackAutomationService) {

        event(new AutomationAttackTimeOut($this->character->user));

        $automation = CharacterAutomation::find($this->automationId);

        if ($this->shouldBail($automation)) {
            if (!is_null($automation)) {
                $automation->delete();
            }

            event (new AutomatedAttackStatus($this->character->user, false));
            event(new UpdateTopBarEvent($this->character->refresh()));

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

                    $data = $attackAutomationService->fetchData($this->character, $automation->refresh());

                    event(new AutomatedAttackDetails($this->character->user, $data));
                }
            }
        }

        $timeTillNext = $processAttackAutomation->processFight($automation, $this->character, $this->attackType);

        if ($timeTillNext <= 0) {
            event (new AutomatedAttackStatus($this->character->user, false));

            event(new UpdateTopBarEvent($this->character->refresh()));
            return;
        }

        AttackAutomation::dispatch($this->character, $automation->id, $this->attackType)->delay($timeTillNext);
    }

    protected function shouldBail(CharacterAutomation $automation = null): bool {

        if (is_null($automation)) {
            return true;
        }

        $activeSession = (new UserOnlineValue())->isOnline($this->character->user);

        if (!$activeSession) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        if (now()->diffInHours($automation->started_at) >= 8) {
            $automation->character->update([
                'is_attack_automation_locked' => true,
            ]);

            event(new ServerMessageEvent($automation->character->user, 'Attack Automation Suspended until tomorrow. You have reached the max time limit for today.'));

            return true;
        }

        return false;
    }
}
