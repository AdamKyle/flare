<?php

namespace App\Game\Skills\Events;

use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use Illuminate\Queue\SerializesModels;

class UpdateSkillEvent {

    use SerializesModels;

    /**
     * @var Skill $skill
     */
    public Skill $skill;

    /**
     * @var Monster|null
     */
    public ?Monster $monster;

    /**
     * Create a new event instance.
     *
     * @param Skill $skill
     * @param Monster|null $monster
     */
    public function __construct(Skill $skill, Monster $monster = null) {
        $this->skill     = $skill;
        $this->monster   = $monster;
    }
}
