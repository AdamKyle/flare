<?php

namespace App\Flare\Events;

use App\Flare\Models\Adventure;
use App\Flare\Models\Skill;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class UpdateSkillEvent
{
    use SerializesModels;

    /**
     * @var Skill $skill
     */
    public $skill;

    public $adventure;

    /**
     * Create a new event instance.
     *
     * @param Skill $skill
     * @param Adaventure $adventure | null
     * @return void
     */
    public function __construct(Skill $skill, Adventure $adventure = null)
    {
        $this->skill     = $skill;
        $this->adventure = $adventure;
    }
}
