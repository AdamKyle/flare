<?php

namespace App\Console\Commands;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Item;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Console\Command;

class GiveExtraEventGoalUniques extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:extra-event-goal-uniques';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(RandomAffixGenerator $randomAffixGenerator) {

        $participatingPlayerIds = GlobalEventParticipation::pluck('character_id')->toArray();

        $characters = Character::whereIn('id', $participatingPlayerIds)->get();

        foreach ($characters as $character) {
            $this->handOutFiveItems($character, $randomAffixGenerator);
        }
    }

    protected function handOutFiveItems(Character $character, RandomAffixGenerator $randomAffixGenerator) {

        for ($i = 1; $i <= 5; $i++) {
            $item = $this->generateItem($character, $randomAffixGenerator);

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);

            $character = $character->refresh();
        }
    }

    protected function generateItem(Character $character, RandomAffixGenerator $randomAffixGenerator): Item {
        $item = Item::where('specialty_type', ItemSpecialtyType::CORRUPTED_ICE)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        $randomAffixGenerator = $randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::LEGENDARY);

        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ]);

        return $newItem->refresh();
    }
}
