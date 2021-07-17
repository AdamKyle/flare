<?php

namespace App\Flare\Events;

use App\Flare\Models\Adventure;
use App\Flare\Models\Monster;
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

    /**
     * @var Adventure|null $adventure
     */
    public $adventure;

    /**
     * @var Monster|null
     */
    public $monster;

    /**
     * Create a new event instance.
     *
     * @param Skill $skill
     * @param Adventure|null $adventure | null
     * @param Monster|null $monster
     */
    public function __construct(Skill $skill, Adventure $adventure = null, Monster $monster = null)
    {
        $this->skill     = $skill;
        $this->adventure = $adventure;
        $this->monster   = $monster;
    }
}
