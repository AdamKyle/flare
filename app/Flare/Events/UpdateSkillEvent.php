<?php

namespace App\Flare\Events;

use App\Flare\Models\Adventure;
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

    public $adventure;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(Skill $skill, Adventure $adventure = null)
    {
        $this->skill     = $skill;
        $this->adventure = $adventure;
    }
}
