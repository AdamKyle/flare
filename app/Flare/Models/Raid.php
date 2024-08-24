<?php

namespace App\Flare\Models;

use Database\Factories\RaidFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Raid extends Model
{
    use HasFactory;

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
        'raid_monster_ids' => 'array',
        'corrupted_location_ids' => 'array',
        'item_specialty_reward_type' => 'string',
    ];

    public function getMonstersForSelection(GameMap $gameMap, array $locationIds): array
    {

        $raidMonsters = Cache::get('raid-monsters');

        if (empty($raidMonsters)) {
            return [];
        }

        if (! isset($raidMonsters[$gameMap->name])) {
            return [];
        }

        $raidMonsters = $raidMonsters[$gameMap->name];

        $newRaidMonsters = [];

        foreach ($raidMonsters as $monster) {
            if (! in_array($this->raid_boss_location_id, $locationIds) && $monster['is_raid_boss']) {
                continue;
            }

            if ($monster['is_raid_boss']) {
                $monster['name'] = $monster['name'].' (RAID BOSS)';
            }

            $newRaidMonsters[] = $monster;
        }

        return $newRaidMonsters;

    }

    public function raidBoss()
    {
        return $this->hasOne(Monster::class, 'id', 'raid_boss_id');
    }

    public function raidBossLocation()
    {
        return $this->hasOne(Location::class, 'id', 'raid_boss_location_id');
    }

    public function artifactItem()
    {
        return $this->hasOne(Item::class, 'id', 'artifact_item_id');
    }

    private function moveRaidBossToTheTopOfTheList(array $raidMonsters, array $locationIds): array
    {
        $raidBossIndex = -1;

        foreach ($raidMonsters as $key => $value) {
            if ($value['is_raid_boss']) {
                $raidBossIndex = $key;

                break;
            }
        }

        if ($raidBossIndex > -1) {
            $raidBoss = array_splice($raidMonsters, $raidBossIndex, 1)[0];

            if (in_array($this->raid_boss_location_id, $locationIds)) {

                $raidBoss['name'] = $raidBoss['name'].' (RAID BOSS)';

                array_unshift($raidMonsters, $raidBoss);
            }
        }

        return $raidMonsters;
    }

    protected static function newFactory()
    {
        return RaidFactory::new();
    }
}
