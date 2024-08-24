<?php

namespace App\Admin\Import\Raids\Sheets;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RaidSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $raidData = array_combine($rows[0]->toArray(), $row->toArray());
                $raid = Raid::where('name', $raidData['name'])->first();

                $cleanRaidData = $this->cleanRaidData($raidData);

                if (empty($cleanRaidData)) {
                    continue;
                }

                if (! isset($cleanRaidData['artifact_item_id'])) {
                    continue;
                }

                $this->handleEvent($cleanRaidData, $raid);
            }
        }
    }

    protected function cleanRaidData(array $raidData): array
    {

        $raidBoss = Monster::where('name', $raidData['raid_boss_id'])->first();
        $raidMonsterIds = Monster::whereIn('name', explode(',', $raidData['raid_monster_ids']))->pluck('id')->toArray();
        $raidBossLocationId = Location::where('name', $raidData['raid_boss_location_id'])->first();
        $raidCorruptedLocations = Location::whereIn('name', explode(',', $raidData['corrupted_location_ids']))->pluck('id')->toArray();

        if (is_null($raidBoss)) {
            return [];
        }

        if (empty($raidMonsterIds)) {
            return [];
        }

        if (is_null($raidBossLocationId)) {
            return [];
        }

        if (empty($raidCorruptedLocations)) {
            return [];
        }

        $raidData['raid_boss_id'] = $raidBoss->id;
        $raidData['raid_monster_ids'] = $raidMonsterIds;
        $raidData['raid_boss_location_id'] = $raidBossLocationId->id;
        $raidData['corrupted_location_ids'] = $raidCorruptedLocations;

        if (isset($raidData['artifact_item_id'])) {
            $item = Item::where('name', $raidData['artifact_item_id'])->first();

            if (! is_null($item)) {
                $raidData['artifact_item_id'] = $item->id;
            } else {
                unset($raidData['artifact_item_id']);
            }
        }

        return $raidData;
    }

    /**
     * Handle updateing or creating data.
     */
    protected function handleEvent(array $eventData, ?Raid $raid = null): void
    {
        if (! is_null($raid)) {

            $raid->update($eventData);
        } else {
            Raid::create($eventData);
        }
    }
}
