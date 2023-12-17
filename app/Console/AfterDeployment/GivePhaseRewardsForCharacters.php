<?php

namespace App\Console\AfterDeployment;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Console\Command;

class GivePhaseRewardsForCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:phase-rewards-for-characters {eventGoalAmount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'gives rewards for global event goal rewards';

    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * Execute the console command.
     */
    public function handle(RandomAffixGenerator $randomAffixGenerator) {

        $this->randomAffixGenerator = $randomAffixGenerator;

        $event = Event::whereIn('type', [
            EventType::WINTER_EVENT,
        ])->first();

        $globalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        Character::whereIn('id', $globalEventGoal->globalEventParticipation->pluck('character_id')->toArray())
            ->chunkById(100, function ($characters) use ($globalEventGoal) {
                foreach ($characters as $character) {

                    $amountOfKills = $globalEventGoal->globalEventParticipation
                        ->where('character_id', $character->id)
                        ->first()
                        ->current_kills;

                    if ($amountOfKills >= $this->fetchKillAmountNeeded($globalEventGoal)) {
                        $this->rewardForCharacter($character, $globalEventGoal);
                    }
                }
            });
    }

    public function fetchKillAmountNeeded(GlobalEventGoal $globalEventGoal): int {
        $participationAmount = $this->argument('eventGoalAmount');

        $participants = $globalEventGoal->globalEventParticipation()->count();

        if ($participants > 0) {
            $participationAmount = round(($participationAmount / $participants));
        }

        return $participationAmount;
    }

    protected function rewardForCharacter(Character $character, GlobalEventGoal $globalEventGoal) {

        $item = Item::where('specialty_type', $globalEventGoal->item_specialty_type_reward)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if ($globalEventGoal->should_be_unique) {

            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::LEGENDARY);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Unique power for participating in the current events global goal.', $slot->id));

            return;
        }

        if ($globalEventGoal->should_be_mythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::MYTHIC);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Mythical power for participating in the current events global goal.', $slot->id));
        }
    }
}
