<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Service\KingdomBuildingUnlockSyncService;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;

class KingdomBuildingUnlockSyncServiceTest extends TestCase
{
    use CreatePassiveSkill, RefreshDatabase;

    public function test_passive_level_greater_than_required_unlocks_existing_building_rows(): void
    {
        $passiveSkill = $this->createPassiveSkill([
            'name' => 'Marketplace',
            'effect_type' => PassiveSkillTypeValue::UNLOCKS_BUILDING,
        ]);
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation();
        $character = $characterFactory->getCharacter();
        $character->passiveSkills()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $passiveSkill->id,
            'parent_skill_id' => null,
            'current_level' => 3,
            'hours_to_next' => 1,
            'started_at' => null,
            'completed_at' => null,
            'is_locked' => false,
        ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'name' => 'Marketplace',
                'is_locked' => true,
                'passive_skill_id' => $passiveSkill->id,
                'level_required' => 2,
            ], [
                'is_locked' => true,
            ])
            ->getKingdom();

        resolve(KingdomBuildingUnlockSyncService::class)->syncForKingdom($kingdom);

        $this->assertFalse($kingdom->refresh()->buildings->first()->is_locked);
    }

    public function test_name_fallback_unlocks_existing_building_rows(): void
    {
        $passiveSkill = $this->createPassiveSkill([
            'name' => 'Marketplace',
            'effect_type' => PassiveSkillTypeValue::UNLOCKS_BUILDING,
        ]);
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation();
        $character = $characterFactory->getCharacter();
        $character->passiveSkills()->create([
            'character_id' => $character->id,
            'passive_skill_id' => $passiveSkill->id,
            'parent_skill_id' => null,
            'current_level' => 3,
            'hours_to_next' => 1,
            'started_at' => null,
            'completed_at' => null,
            'is_locked' => false,
        ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'name' => 'Marketplace',
                'is_locked' => true,
                'passive_skill_id' => null,
                'level_required' => 2,
            ], [
                'is_locked' => true,
            ])
            ->getKingdom();

        resolve(KingdomBuildingUnlockSyncService::class)->syncForKingdom($kingdom);

        $this->assertFalse($kingdom->refresh()->buildings->first()->is_locked);
    }
}
