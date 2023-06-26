<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Raid extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'story',
        'raid_boss_id',
        'raid_monster_ids',
        'raid_boss_location_id',
        'corrupted_location_ids',
        'item_specialty_reward_type',
        'artifact_item_id',
    ];

    protected $casts = [
        'raid_monster_ids'       => 'array',
        'corrupted_location_ids' => 'array',
        'item_specialty_reward_type' => 'string',
    ];

    public function getMonstersForSelection(): array {
        $monstersArray = $this->raid_monster_ids;
        
        array_unshift($monstersArray, $this->raid_boss_id);

        $raidMonsters = Monster::whereIn('id', $monstersArray)->select('name', 'id', 'is_raid_boss')->get()->toArray();

        return $this->moveRaidBossToTheTopOfTheList($raidMonsters);
    }

    public function raidBoss() {
        return $this->hasOne(Monster::class, 'id', 'raid_boss_id');
    }

    public function raidBossLocation() {
        return $this->hasOne(Location::class, 'id', 'raid_boss_location_id');
    }

    public function artifactItem() {
        return $this->hasOne(Item::class, 'id', 'artifact_item_id');
    }

    private function moveRaidBossToTheTopOfTheList(array $raidMonsters): array {
        $raidBossIndex = -1;

        foreach ($raidMonsters as $key => $value) {
            if ($value['is_raid_boss']) {
                $raidBossIndex = $key;

                break;
            }
        }

        if ($raidBossIndex > -1) {
            $raidBoss = array_splice($raidMonsters, $raidBossIndex, 1)[0];

            $raidBossRecord = RaidBoss::where('raid_boss_id', $raidBoss['id'])->first();

            if (!$raidBossRecord->boss_current_hp > 0) {

                $raidBoss['name'] = $raidBoss['name'] . ' (RAID BOSS)';

                array_unshift($raidMonsters, $raidBoss);
            }
        }

        return $raidMonsters;
    }
}
