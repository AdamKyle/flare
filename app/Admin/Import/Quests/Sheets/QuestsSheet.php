<?php

namespace App\Admin\Import\Quests\Sheets;

use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $quest = array_combine($rows[0]->toArray(), $row->toArray());

                $questData = $this->returnCleanItem($quest);

                if (!empty($questData)) {
                    Quest::updateOrCreate(['name' => $questData['name']], $questData);
                }
            }
        }
    }

    protected function returnCleanItem(array $quest) {
        $npc = Npc::where('real_name', $quest['npc_id'])->first();

        if (is_null($npc)) {
            return [];
        }

        $quest['npc_id'] = $npc->id;

        if (isset($quest['item_id'])) {
            $requiredItem = Item::where('name', $quest['item_id'])->first();

            if (is_null($requiredItem)) {
                return [];
            }

            $quest['item_id'] = $requiredItem->id;
        }

        if (isset($quest['reward_item'])) {
            $item = Item::where('name', $quest['reward_item'])->first();

            if (is_null($item)) {
                return [];
            }

            $quest['reward_item'] = $item->id;
        }

        if (!isset($quest['unlocks_skill'])) {
            $quest['unlocks_skill'] = false;
        }

        return $quest;
    }
}
