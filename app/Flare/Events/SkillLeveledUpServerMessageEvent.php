<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\Flare\Models\User;

class SkillLeveledUpServerMessageEvent
{
    use SerializesModels;

    /**
     * Type of server messsage.
     *
     * @var string $type
     */
    public $skill;

    /**
     * User
     *
     * @var \App\Flare\Models\User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \App\Flare\Models\User $user
     * @return void
     */
    public function __construct(User $user, Skill $skill)
    {
        $this->skill        = $skill;
        $this->user         = $user;
    }
}
