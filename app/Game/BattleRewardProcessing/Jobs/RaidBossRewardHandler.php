<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Concerns\HandleGivingAncestorItem;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Maps\Services\Common\UpdateRaidMonstersForLocation;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RaidBossRewardHandler implements ShouldQueue
{
    use Dispatchable, HandleGivingAncestorItem, InteractsWithQueue, Queueable, SerializesModels, UpdateRaidMonstersForLocation;

    private int $characterId;

    private ?int $raidId;

    private int $monsterId;

    public function __construct(int $characterId, int $monsterId, ?int $raidId = null)
    {
        $this->characterId = $characterId;
        $this->raidId = $raidId;
        $this->monsterId = $monsterId;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function handle(BattleEventHandler $battleEventHandler)
    {
        $character = Character::find($this->characterId);

        $battleEventHandler->processMonsterDeath($this->characterId, $this->monsterId);

        if (! is_null($this->raidId)) {

            $raid = Raid::find($this->raidId);

            $this->handleWhenRaidBossIsKilled($character, $raid->raidBoss);

            $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

            $this->updateMonstersForRaid($character, $location);
        }
    }

    /**
     * Handle the raid boss when its killed.
     *
     * - No one can attack anymore. The attacks will not reset.
     * - Give ancestral item to winner.
     * - Give top 10 damage dealers a piece of gear.
     */
    private function handleWhenRaidBossIsKilled(Character $charater, Monster $raidBoss): void
    {
        event(new GlobalMessageEvent($charater->name . ' Has slaughted: ' . $raidBoss->name . ' and has recieved a special Ancient gift from The Poet him self!'));

        $raid = Raid::find($this->raidId);

        $this->giveAncientReward($charater, $raid->artifact_item_id);

        $this->giveGearReward($raid);

        RaidBossParticipation::chunkById(250, function ($participationRecords) {
            foreach ($participationRecords as $record) {
                $record->update([
                    'attacks_left' => 0,
                ]);

                $record = $record->refresh();

                event(new UpdateRaidAttacksLeft($record->character->user_id, 0, $record->damage_dealt));
            }
        });
    }

    private function giveGearReward(Raid $raid)
    {
        $raidParticipation = RaidBossParticipation::where('raid_id', $raid->id)->orderBy('damage_dealt', 'asc')->take(10)->get();

        foreach ($raidParticipation as $participator) {

            $item = Item::where('specialty_type', $raid->item_specialty_reward_type)->inRandomOrder()->first();

            if ($participator->character->isInventoryFull()) {
                event(new ServerMessageEvent($participator->character->user, 'Your inventory was full. You got no item. Make sure to clear room next time!'));

                return;
            }

            if (! is_null($item)) {
                $validSocketTypes = [
                    'weapon',
                    'sleeves',
                    'gloves',
                    'feet',
                    'body',
                    'shield',
                    'helmet',
                ];

                $duplicatedItem = $item->duplicate();

                $duplicatedItem->update([
                    'holy_stacks' => 20,
                ]);

                $duplicatedItem = $duplicatedItem->refresh();

                if (in_array($duplicatedItem->type, $validSocketTypes)) {

                    $duplicatedItem->update([
                        'socket_count' => rand(0, 6),
                    ]);

                    $duplicatedItem = $duplicatedItem->refresh();
                }

                $slot = $participator->character->inventory->slots()->create([
                    'inventory_id' => $participator->character->inventory->id,
                    'item_id' => $duplicatedItem->id,
                ]);

                event(new ServerMessageEvent($participator->character->user, 'You were given: ' . $slot->item->name, $slot->id));

                event(new GlobalMessageEvent('Congratulations to: ' . $participator->character->name . ' for doing: ' . number_format($participator->damage_dealt) . ' total Damage to the raid boss! They have recieved a godly gift!'));
            }
        }
    }
}
