<?php

namespace App\Game\PassiveSkills\Jobs;

use App\Flare\Models\Notification as Notification;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\PassiveSkills\Events\UpdatePassiveSkillTimer;

class TrainPassiveSkill implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $character;

    private $characterPassiveSkill;

    /**
     * @param Character $character
     * @param int $itemToCraft
     */
    public function __construct(Character $character, CharacterPassiveSkill $characterPassiveSkill)
    {
        $this->character             = $character;
        $this->characterPassiveSkill = $characterPassiveSkill;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {

        if (is_null($this->characterPassiveSkill->started_at)) {
            return;
        }

        if (!$this->characterPassiveSkill->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $this->characterPassiveSkill->completed_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            TrainPassiveSkill::dispatch(
                $this->character,
                $this->characterPassiveSkill
            )->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $newLevel = $this->characterPassiveSkill->current_level + 1;

        if ($newLevel > $this->characterPassiveSkill->passiveSkill->max_level) {
            $newLevel = $this->characterPassiveSkill->passiveSkill->max_level;
        }

        $this->characterPassiveSkill->update([
            'started_at'    => null,
            'completed_at'  => null,
            'current_level' => $newLevel,
            'hours_to_next' => ($newLevel + 1) * $this->characterPassiveSkill->passiveSkill->hours_per_level,
        ]);

        $newPassive = $this->characterPassiveSkill->refresh();

        $children   = $newPassive->passiveSkill->childSkills;

        foreach ($children as $child) {
            if ($child->unlocks_at_level >= $newPassive->current_level) {
                $foundChild = $this->character->passiveSkills()->where('passive_skill_id', $child->id)->first();

                $foundChild->update([
                    'is_locked' => false,
                ]);
            }
        }

        $this->createNotifactionEvent($newPassive);

        event(new UpdateTopBarEvent($this->character->refresh()));
    }

    protected function createNotifactionEvent(CharacterPassiveSkill $characterPassiveSkill) {
        Notification::create([
            'character_id' => $this->character->id,
            'title'        => $characterPassiveSkill->passiveSkill->name,
            'message'      => $characterPassiveSkill->passiveSkill->name . ' skill has gained a new level! Check your character sheet!',
            'status'       => 'success',
            'type'         => 'passive-skill',
            'url'          => route('game.character.sheet'),
        ]);

        $character = $this->character->refresh();

        event(new UpdateNotificationsBroadcastEvent($character->notifications()->where('read', false)->get(), $character->user));
    }
}