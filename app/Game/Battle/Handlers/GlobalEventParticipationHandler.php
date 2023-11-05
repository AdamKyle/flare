<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Messages\Events\ServerMessageEvent;

class GlobalEventParticipationHandler {

    private RandomAffixGenerator $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    public function handleGlobalEventParticipation(Character $character, GlobalEventGoal $globalEventGoal) {
        if ($globalEventGoal->total_kills >= $globalEventGoal->max_kills) {
            return;
        }

        $globalEventParticipation = $character->globalEventParticipation;

        if ($globalEventParticipation->isEmpty()) {
            $character->globalEventParticipation()->create([
                'global_event_goal_id' => $globalEventGoal->id,
                'character_id'         => $character->id,
                'current_kills'        => 1,
            ]);

            return;
        }

        $character->globalEventParticipation()->update([
            'current_kills' => $character->globalEventParticipation->current_kills + 1,
        ]);

        $globalEventGoal = $globalEventGoal->refresh();

        if ($globalEventGoal->total_kills >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every_kills;

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_kills ? $globalEventGoal->max_kills : $newAmount,
            ]);

            $this->rewardCharactersParticipating($globalEventGoal->refresh());
        }
    }

    protected function rewardCharactersParticipating(GlobalEventGoal $globalEventGoal) {
        Character::whereIn('id', $globalEventGoal->pluck('globalEventParticipation.character_id')->toArray())
            ->chunkById(100, function ($characters) use ($globalEventGoal) {
                foreach ($characters as $character) {
                    $this->rewardForCharacter($character, $globalEventGoal);
                }
            });
    }

    protected function rewardForCharacter(Character $character, GlobalEventGoal $globalEventGoal) {
        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character);

        $item = Item::where('specialty_type', $globalEventGoal->item_specialty_type_reward)
            ->whereIsNull('item_prefix_id')
            ->whereIsNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if ($globalEventGoal->is_unique) {
            $randomAffixGenerator = $this->randomAffixGenerator->setPaidAmount(RandomAffixDetails::LEGENDARY);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            if ($character->isInventoryFull()) {
                event(new ServerMessageEvent($character->user, 'Your characters inventory is full. You were rewarded the Event Item either way.'));
            }
        }

        if ($globalEventGoal->is_mythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setPaidAmount(RandomAffixDetails::MYTHIC);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of great power for participating in the current events global goal'));

            if ($character->isInventoryFull()) {
                event(new ServerMessageEvent($character->user, 'Your characters inventory is full. You were rewarded the Event Item either way.'));
            }
        }
    }
}
