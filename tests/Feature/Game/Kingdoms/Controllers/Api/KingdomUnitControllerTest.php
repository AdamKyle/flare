<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomUnitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testManualRecruitRejectsCapitalCityQueuedUnit(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        CapitalCityUnitQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/' . $kingdom->id . '/recruit-units/' . $unit->id, [
                'amount' => 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Unit is already in the process of recruiting.',
        ]);
        $this->assertSame(0, UnitInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function testManualRecruitValidNonQueuedUnitWorks(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory->updateSkill('Kingmanship', [
            'skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value,
        ]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
        ])->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        GameBuildingUnit::factory()->create([
            'game_building_id' => $building->game_building_id,
            'game_unit_id' => $unit->id,
            'required_level' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/' . $kingdom->id . '/recruit-units/' . $unit->id, [
                'amount' => 1,
            ]);

        $response->assertOk();
        $this->assertSame(1, UnitInQueue::where('kingdom_id', $kingdom->id)
            ->where('game_unit_id', $unit->id)
            ->count());
    }

    public function testManualRecruitIgnoresExpiredManualQueuedUnit(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory->updateSkill('Kingmanship', [
            'skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value,
        ]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
        ])->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        GameBuildingUnit::factory()->create([
            'game_building_id' => $building->game_building_id,
            'game_unit_id' => $unit->id,
            'required_level' => 1,
        ]);
        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 10,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/' . $kingdom->id . '/recruit-units/' . $unit->id, [
                'amount' => 1,
            ]);

        $response->assertOk();
        $this->assertSame(2, UnitInQueue::where('kingdom_id', $kingdom->id)
            ->where('game_unit_id', $unit->id)
            ->count());
    }
}
