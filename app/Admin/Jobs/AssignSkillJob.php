<?php

namespace App\Admin\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Values\BaseSkillValue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignSkillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $characterId;

    public $skillId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $characterId, int $skillId)
    {
        $this->characterId = $characterId;
        $this->skillId = $skillId;
    }

    public function handle(BaseSkillValue $baseSkillValue)
    {
        $character = Character::find($this->characterId);

        $character->skills()->create(
            $baseSkillValue->getBaseCharacterSkillValue($character, GameSkill::find($this->skillId))
        );
    }
}
