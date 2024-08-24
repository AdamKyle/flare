<?php

namespace App\Game\Skills\Events;

use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use Illuminate\Queue\SerializesModels;

class UpdateSkillEvent
{
    use SerializesModels;

    public Skill $skill;

    public ?Monster $monster;

    /**
     * Create a new event instance.
     */
    public function __construct(Skill $skill, ?Monster $monster = null)
    {
        $this->skill = $skill;
        $this->monster = $monster;
    }
}
