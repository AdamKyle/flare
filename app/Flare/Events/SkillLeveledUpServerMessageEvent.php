<?php

namespace App\Flare\Events;

use App\Flare\Models\Skill;
use App\Flare\Models\User;
use Illuminate\Queue\SerializesModels;

class SkillLeveledUpServerMessageEvent
{
    use SerializesModels;

    /**
     * @var Skill
     */
    public $skill;

    /**
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Skill $skill)
    {
        $this->skill = $skill;
        $this->user = $user;
    }
}
