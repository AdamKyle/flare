<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\GemAbilityExecutor;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use Tests\TestCase;

class GemAbilityExecutorTest extends TestCase
{
    /**
     * Prove an active ability uses cached scaling data and emits its concise battle message.
     *
     * @return void
     */
    public function test_active_ability_deals_cached_scaled_damage(): void
    {
        $character = new Character(['id' => 10]);
        $cache = $this->createMock(CharacterCacheData::class);
        $chance = $this->createMock(ChanceCalculator::class);
        $cache->expects($this->once())->method('getDataFromAttackCache')->with($character, 'attack')->willReturn($this->attackData(200));
        $chance->expects($this->once())->method('passesPercentage')->with(20.0)->willReturn(true);

        $result = (new GemAbilityExecutor($cache, $chance))->execute($character, 'attack', 100, 1_000, false);

        $this->assertSame(930.0, $result['monster_health']);
        $this->assertSame('Flame Burst erupts from your socketed Gem and deals 70 bonus damage.', $result['messages'][0]['message']);
    }

    /**
     * Prove active Gem Ability damage respects the existing Raid player-damage cap.
     *
     * @return void
     */
    public function test_active_ability_damage_respects_raid_cap(): void
    {
        $character = new Character(['id' => 10]);
        $cache = $this->createMock(CharacterCacheData::class);
        $chance = $this->createMock(ChanceCalculator::class);
        $cache->expects($this->once())->method('getDataFromAttackCache')->willReturn($this->attackData(8_000_000_000_000));
        $chance->expects($this->once())->method('passesPercentage')->willReturn(true);

        $result = (new GemAbilityExecutor($cache, $chance))->execute($character, 'attack', 100, 5_000_000_000_000, true);

        $this->assertSame(5_000_000_000_000 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES, $result['monster_health']);
    }

    /**
     * Build one cache-safe active Gem Ability attack payload.
     *
     * @param int $weaponDamage
     * @return array
     */
    private function attackData(int $weaponDamage): array
    {
        return [
            'weapon_damage' => $weaponDamage,
            'gem_abilities' => [
                'active' => [[
                    'name' => 'Flame Burst',
                    'proc_chance' => 0.20,
                    'effect_value' => 0.35,
                    'scaling_source' => 'weapon_attack',
                ]],
            ],
        ];
    }
}
