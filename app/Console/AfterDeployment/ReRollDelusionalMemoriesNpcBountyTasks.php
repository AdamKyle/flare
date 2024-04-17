<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Events\Values\EventType;
use Illuminate\Console\Command;
use App\Flare\Models\GameMap;
use App\Flare\Values\MapNameValue;


class ReRollDelusionalMemoriesNpcBountyTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 're-roll:delusional-memories-npc-bounty-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {
        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();

        $factionLoyalties = FactionLoyalty::where('is_pledged', true)->get();

        foreach ($factionLoyalties as $loyalty) {
            if ($loyalty->faction->game_map_id !== $gameMap->id) {
                continue;
            }

            $npcs = $loyalty->factionLoyaltyNpcs;

            foreach ($npcs as $npc) {
                $task = $npc->factionLoyaltyNpcTasks;

                $bountyTasks = $this->createBountyTasks($loyalty->character, $gameMap);

                $indexToGrab = 0;

                $fameTasks = $task->fame_tasks;

                foreach ($fameTasks as $index => $taskForNpc) {

                    if ($taskForNpc['type'] === 'bounty') {
                        $fameTasks[$index] = $bountyTasks[$indexToGrab];

                        $indexToGrab++;
                    }
                }

                $task->update([
                    'fame_tasks' => $fameTasks
                ]);
            }

        }
    }

    protected function createBountyTasks(Character $character, GameMap $gameMap): array {

        $tasks = [];

        $monster = null;

        if (!is_null($gameMap->only_during_event_type)) {

            $hasPurgatoryItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::PURGATORY;
            })->first();

            if (is_null($hasPurgatoryItem)) {
                $monster = Monster::where('game_map_id', GameMap::where('name', MapNameValue::SURFACE)->first()->id);
            }
        }

        if (is_null($monster)) {
            $monster = Monster::where('game_map_id', $gameMap->id);
        }

        while (count($tasks) < 3) {

            $monster = $monster->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNull('only_for_location_type')
                ->inRandomOrder()
                ->first();

            if ($this->hasTaskAlready($tasks, 'monster_id', $monster->id)) {
                continue;
            }

            $amount = rand(10, 50);

            $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

            if (!is_null($event)) {
                $amount = ceil($amount / 2);
            }

            if ($amount <= 0) {
                $amount = 5;
            }

            $tasks[] = [
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => $amount,
                'current_amount'  => 0,
            ];
        }

        return $tasks;
    }

    private function hasTaskAlready(array $tasks, string $key, int $id): bool {
        foreach ($tasks as $task) {
            if ($task[$key] === $id) {
                return true;
            }
        }

        return false;
    }
}
