<?php

namespace App\Console\Commands;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ReImportAlchemyItems extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 're-import:alchemy-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-imports Alchemy Items and refunds players';

    /**
     * Execute the console command.
     */
    public function handle(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $data = $this->getAlchemyItemInformationFromCharacters();

        $this->cleanUpAndRefundAlchemyItemsFromCharacters($updateCharacterAttackTypes, $data);

        $this->deleteAndReImportAlchemyItems();
    }

    protected function deleteAndReImportAlchemyItems(): void {
        $items = Item::where('type', 'alchemy')->get();

        foreach ($items as $item) {
            $item->delete();
        }

        Artisan::call('import:game-data Items');
    }

    protected function cleanUpAndRefundAlchemyItemsFromCharacters(UpdateCharacterAttackTypes $updateCharacterAttackTypes, array $characterAlchemyInfo): void {
        foreach ($characterAlchemyInfo as $characterId => $data) {
            $character = Character::find($characterId);

            $newGoldDust = $data['total_gold_dust'] + $character->gold_dust;
            $newShards   = $data['total_shards'] + $character->shards;

            if ($newGoldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
                $newGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            if ($newShards > MaxCurrenciesValue::MAX_GOLD_DUST) {
                $newShards = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            $character->update([
                'shards' => $newShards,
                'gold_dust' => $newGoldDust,
            ]);

            $character->inventory->slots()->whereIn('item_id', $data['items'])->delete();

            $character = $character->refresh();

            $updateCharacterAttackTypes->updateCache($character);

            event(new UpdateTopBarEvent($character));
        }
    }

    protected function getAlchemyItemInformationFromCharacters(): array {
        $characters = Character::whereHas('inventory.slots.item', function ($query) {
            $query->where('type', 'alchemy');
        })->get();

        $result = [];

        foreach ($characters as $character) {
            $alchemyItems = $character->inventory->slots
                ->where('item.type', 'alchemy')
                ->pluck('item.id')
                ->toArray();

            $totalGoldDust = $character->inventory->slots
                ->where('item.type', 'alchemy')
                ->sum('item.gold_dust_cost');

            $totalShards = $character->inventory->slots
                ->where('item.type', 'alchemy')
                ->sum('item.shards_cost');

            $result[$character->id] = [
                'items' => $alchemyItems,
                'total_gold_dust' => $totalGoldDust,
                'total_shards' => $totalShards,
            ];
        }

        return $result;
    }
}
