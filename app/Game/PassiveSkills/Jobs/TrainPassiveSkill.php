<?php

namespace App\Game\PassiveSkills\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Service\KingdomBuildingUnlockSyncService;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\PassiveSkills\Events\UpdatePassiveTree;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class TrainPassiveSkill implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Character $character;

    private CharacterPassiveSkill $characterPassiveSkill;

    public function __construct(Character $character, CharacterPassiveSkill $characterPassiveSkill)
    {
        $this->character = $character;
        $this->characterPassiveSkill = $characterPassiveSkill;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        Manager $manager,
        KingdomTransformer $kingdomTransformer,
        KingdomMaxResourceRecalculationService $kingdomMaxResourceRecalculationService,
        KingdomBuildingUnlockSyncService $kingdomBuildingUnlockSyncService
    ) {

        if (is_null($this->characterPassiveSkill->started_at)) {
            return;
        }

        $maxLevel = $this->characterPassiveSkill->passiveSkill->max_level;

        if ($this->characterPassiveSkill->current_level >= $maxLevel) {
            $this->characterPassiveSkill->update([
                'started_at' => null,
                'completed_at' => null,
                'current_level' => $maxLevel,
                'hours_to_next' => 0,
            ]);

            return;
        }

        if (! $this->characterPassiveSkill->completed_at->lessThanOrEqualTo(now())) {
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

        $newLevel = min($this->characterPassiveSkill->current_level + 1, $maxLevel);
        $hoursToNext = $newLevel >= $maxLevel
            ? 0
            : ($newLevel + 1) * $this->characterPassiveSkill->passiveSkill->hours_per_level;

        $this->characterPassiveSkill->update([
            'started_at' => null,
            'completed_at' => null,
            'current_level' => $newLevel,
            'hours_to_next' => $hoursToNext,
        ]);

        $newPassive = $this->characterPassiveSkill->refresh();

        $children = $newPassive->passiveSkill->childSkills;

        foreach ($children as $child) {
            if ($newPassive->current_level >= $child->unlocks_at_level) {
                $foundChild = $this->character->passiveSkills()->where('passive_skill_id', $child->id)->first();

                if (! is_null($foundChild)) {
                    $foundChild->update([
                        'is_locked' => false,
                    ]);
                }
            }
        }

        if ($newPassive->passiveSkill->passiveType()->unlocksBuilding()) {
            $kingdoms = $this->character->kingdoms;

            foreach ($kingdoms as $kingdom) {

                $kingdomBuildingUnlockSyncService->syncForKingdom($kingdom);

                $kingdom = new Item($kingdom->refresh(), $kingdomTransformer);
                $kingdom = $manager->createData($kingdom)->toArray();
                $user = $this->character->user;

                event(new UpdateKingdom($user, $kingdom));
            }
        }

        if ($newPassive->passiveSkill->passiveType()->isResourceIncrease()) {
            $kingdoms = $this->character->kingdoms;

            foreach ($kingdoms as $kingdom) {
                $kingdomMaxResourceRecalculationService->recalculate($kingdom);

                $kingdom = new Item($kingdom->refresh(), $kingdomTransformer);
                $kingdom = $manager->createData($kingdom)->toArray();
                $user = $this->character->user;

                event(new UpdateKingdom($user, $kingdom));
            }
        }

        if ($newPassive->passiveSkill->passiveType()->isSteelIncrease()) {
            $kingdoms = $this->character->kingdoms;

            foreach ($kingdoms as $kingdom) {
                $kingdom->update([
                    'max_steel' => $kingdom->max_steel + $newPassive->passiveSkill->resource_bonus_per_level,
                ]);

                $kingdom = new Item($kingdom->refresh(), $kingdomTransformer);
                $kingdom = $manager->createData($kingdom)->toArray();
                $user = $this->character->user;

                event(new UpdateKingdom($user, $kingdom));
            }
        }

        $character = $this->character->Refresh();

        event(new ServerMessageEvent($character->user, $newPassive->passiveSkill->name . ' skill has gained a new level! Check your character sheet!'));

        event(new UpdatePassiveTree($character->user, $characterPassiveSkills->getPassiveSkills($character)));
    }
}
