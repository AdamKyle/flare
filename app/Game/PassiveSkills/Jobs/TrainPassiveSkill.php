<?php

namespace App\Game\PassiveSkills\Jobs;

use App\Flare\Models\GameBuilding;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\PassiveSkills\Events\UpdatePassiveTree;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\Notification as Notification;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Game\Kingdoms\Events\UpdateKingdom;
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
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer) {

        if (is_null($this->characterPassiveSkill->started_at)) {
            return;
        }

        if (!$this->characterPassiveSkill->completed_at->lessThanOrEqualTo(now())) {
            // @codeCoverageIgnoreStart
            $timeLeft = $this->characterPassiveSkill->completed_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

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
            if ($newPassive->current_level >= $child->unlocks_at_level) {
                $foundChild = $this->character->passiveSkills()->where('passive_skill_id', $child->id)->first();

                if (!is_null($foundChild)) {
                    $foundChild->update([
                        'is_locked' => false,
                    ]);
                }
            }
        }

        if ($newPassive->passiveSkill->passiveType()->unlocksBuilding()) {
            $kingdoms     = $this->character->kingdoms;
            $gameBuilding = GameBuilding::where('name', $newPassive->passiveSkill->name)->first();

            foreach ($kingdoms as $kingdom) {

                $kingdom->buildings()->where('game_building_id', $gameBuilding->id)->update([
                    'is_locked' => false,
                ]);

                $kingdom  = new Item($kingdom->refresh(), $kingdomTransformer);
                $kingdom  = $manager->createData($kingdom)->toArray();
                $user     = $this->character->user;

                event(new UpdateKingdom($user, $kingdom));
            }
        }

        $character = $this->character->Refresh();

        event(new ServerMessageEvent($character->user, $newPassive->passiveSkill->name . ' skill has gained a new level! Check your character sheet!'));

        event(new UpdatePassiveTree($character->user, $character->passiveSkills));
    }
}
