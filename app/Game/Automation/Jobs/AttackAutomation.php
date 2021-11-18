<?php

namespace App\Game\Automation\Jobs;

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

    public function handle(ProcessAttackAutomation $processAttackAutomation) {
        $automation = CharacterAutomation::find($this->automationId);

        if ($this->shouldBail($automation)) {
            return;
        }

        $processAttackAutomation->processFight($automation, $this->character, $this->attackType);
    }

    protected function shouldBail(CharacterAutomation $automation): bool {

        if (is_null($automation)) {
            return true;
        }

        $activeSession = (new UserOnlineValue())->isOnline($this->character->user);

        if (!$activeSession) {
            return true;
        }

        if (now()->diffInHours($automation->started_at) >= 8) {
            return true;
        }

        return false;
    }
}
