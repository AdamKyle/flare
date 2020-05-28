<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\Skill;
use Illuminate\Queue\SerializesModels;
use App\User;

class UpdateSkillEvent
{
    use SerializesModels;

    /**
     * @var \App\Flare\Models\Skill
     */
    public $skill;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(Skill $skill)
    {
        $this->skill = $skill;
    }
}
