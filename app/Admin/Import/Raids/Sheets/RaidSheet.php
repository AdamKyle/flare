<?php

namespace App\Admin\Import\Raids\Sheets;

use App\Flare\Models\Item;
use App\Flare\Models\Raid;
use App\Flare\Models\Monster;
use App\Flare\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RaidSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $raidData = array_combine($rows[0]->toArray(), $row->toArray());
                $raid     = Raid::where('name', $raidData['name'])->first();

                $cleanRaidData = $this->cleanRaidData($raidData);

                if (empty($cleanRaidData)) {
                    continue;
                }

                if (!isset($cleanRaidData['artifact_item_id'])) {
                    continue;
                }

                $this->handleEvent($cleanRaidData, $raid);
            }
        }
    }

    protected function cleanRaidData(array $raidData): array {

        $raidBoss               = Monster::find($raidData['raid_boss_id']);
        $raidMonsterIds         = Monster::whereIn('id', explode(',', $raidData['raid_monster_ids']))->pluck('id')->toArray();
        $raidBossLocationId     = Location::find($raidData['raid_boss_location_id']);
        $raidCorruptedLocations = Location::whereIn('id', explode(',', $raidData['corrupted_location_ids']))->pluck('id')->toArray();

        if (is_null($raidBoss)) {
            return [];  
        }

        if (empty($raidMonsterIds)) {
            return [];
        }

        if (is_null($raidBossLocationId)) {
            return[];
        }

        if (empty($raidCorruptedLocations)) {
            return [];
        }

        $raidData['raid_boss_id']           = $raidBoss->id;
        $raidData['raid_monster_ids']       = $raidMonsterIds;
        $raidData['raid_boss_location_id']  = $raidBossLocationId->id;
        $raidData['corrupted_location_ids'] = $raidCorruptedLocations;

        if (isset($raidData['artifact_item_id'])) {
            $item = Item::where('name', $raidData['artifact_item_id'])->first();

            if (!is_null($item)) {
                $raidData['artifact_item_id'] = $item->id;
            } else {
                unset($raidData['artifact_item_id']);
            }
        }

        return $raidData;
    }

    /**
     * Handle updateing or creating data.
     *
     * @param array $eventData
     * @param Raid|null $raid
     * @return void
     */
    protected function handleEvent(array $eventData, ?Raid $raid = null): void {
        if (!is_null($raid)) {

            $raid->update($eventData);
        } else {
            Raid::create($eventData);
        }
    }
}
