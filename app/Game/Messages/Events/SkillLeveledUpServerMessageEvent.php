<?php

namespace App\Game\Messages\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Skill;
use App\User;

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
     * @var \App\User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(User $user, Skill $skill)
    {
        $this->skill        = $skill;
        $this->user         = $user;
    }
}
