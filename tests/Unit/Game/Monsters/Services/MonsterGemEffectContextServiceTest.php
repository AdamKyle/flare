<?php

namespace Tests\Unit\Game\Monsters\Services;

use App\Game\Monsters\Services\MonsterGemEffectContextService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;

class MonsterGemEffectContextServiceTest extends TestCase
{
    use CreateGameMap, CreateMonster, RefreshDatabase;

    public function test_for_monster_exposes_to_hit_base_criticality_spell_damage_and_affix_damage_changes(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Detail Context Map']);
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'damage_stat' => 'str',
            'str' => 10,
            'criticality' => 0.1,
            'max_spell_damage' => 50,
            'max_affix_damage' => 20,
        ]);

        Cache::put(MonsterCacheKey::MONSTERS->value, [
            $gameMap->name => [
                'data' => [[
                    'id' => $monster->id,
                    'gem_effect_context' => [
                        'has_effects' => true,
                        'context_type' => 'map',
                        'context_label' => $gameMap->name,
                        'game_map' => ['id' => $gameMap->id, 'name' => $gameMap->name],
                        'location' => null,
                        'sources' => [],
                        'character_power_reduction' => 0.0,
                    ],
                    'to_hit_base' => 15,
                    'criticality' => 0.2,
                    'spell_damage' => 75,
                    'max_affix_damage' => 30,
                ]],
            ],
        ]);

        $contexts = (new MonsterGemEffectContextService)->forMonster($monster);

        $changedFields = collect($contexts[0]['changed_values'])->pluck('field')->all();

        $this->assertContains('to_hit_base', $changedFields);
        $this->assertContains('criticality', $changedFields);
        $this->assertContains('spell_damage', $changedFields);
        $this->assertContains('max_affix_damage', $changedFields);
    }
}
