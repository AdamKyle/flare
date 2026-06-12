<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use Illuminate\Console\Command;

class MoveAlchemyAndGemsToBags extends Command
{
    protected $signature = 'move:alchemy-and-gems-to-bags {--apply : Apply changes instead of dry-running}';

    protected $description = 'Moves old alchemy inventory slots into alchemy bags. Does not move or touch gems, socketed gems, gem inventory slots, or gem bag slots.';

    public function handle(): void
    {
        $scanned = 0;
        $alchemySlotsMoved = 0;
        $alchemyAmountMoved = 0;
        $overLimitAlchemy = 0;

        Character::orderBy('id')->chunk(100, function ($characters) use (
            &$scanned,
            &$alchemySlotsMoved,
            &$alchemyAmountMoved,
            &$overLimitAlchemy
        ) {
            foreach ($characters as $character) {
                $scanned++;

                AlchemyBag::firstOrCreate(['character_id' => $character->id]);

                $inventory = Inventory::where('character_id', $character->id)->first();

                if (is_null($inventory)) {
                    continue;
                }

                $alchemySlots = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)
                    ->join('items', 'items.id', '=', 'inventory_slots.item_id')
                    ->where('items.type', 'alchemy')
                    ->select('inventory_slots.*')
                    ->get();

                if (! $this->option('apply')) {
                    $this->line(
                        "[dry-run] character_id={$character->id}"
                        . " alchemy_slots={$alchemySlots->count()}"
                    );
                    $alchemySlotsMoved += $alchemySlots->count();
                    $alchemyAmountMoved += $alchemySlots->count();
                    continue;
                }

                [$movedAlchemySlots, $movedAlchemyAmount] = $this->moveAlchemySlots($character, $alchemySlots);

                $alchemySlotsMoved += $movedAlchemySlots;
                $alchemyAmountMoved += $movedAlchemyAmount;

                $character = $character->refresh();

                if ($character->getAlchemyBagCount() > $character->alchemy_bag_limit) {
                    $overLimitAlchemy++;
                }
            }
        });

        $mode = $this->option('apply') ? 'applied' : 'dry-run';
        $this->line("Characters scanned: {$scanned}");
        $this->line("Alchemy slots moved: {$alchemySlotsMoved}");
        $this->line("Alchemy amount moved: {$alchemyAmountMoved}");
        $this->line("Over-limit alchemy bags found: {$overLimitAlchemy}");
        $this->line("Done ({$mode}).");
    }

    /**
     * @return array{int, int}
     */
    private function moveAlchemySlots(Character $character, $alchemySlots): array
    {
        $alchemyBag = AlchemyBag::firstOrCreate(['character_id' => $character->id]);
        $slotsMoved = 0;
        $amountMoved = 0;

        foreach ($alchemySlots as $slot) {
            $existingSlot = AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
                ->where('item_id', $slot->item_id)
                ->first();

            if (! is_null($existingSlot)) {
                $existingSlot->update(['amount' => $existingSlot->amount + 1]);
            } else {
                AlchemyBagSlot::create([
                    'alchemy_bag_id' => $alchemyBag->id,
                    'character_id' => $character->id,
                    'item_id' => $slot->item_id,
                    'amount' => 1,
                ]);
            }

            $slot->delete();
            $slotsMoved++;
            $amountMoved++;
        }

        return [$slotsMoved, $amountMoved];
    }
}
