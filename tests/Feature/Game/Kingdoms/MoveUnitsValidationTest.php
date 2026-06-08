<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Requests\MoveUnitsRequest;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MoveUnitsValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_unit_move_amount_is_rejected(): void
    {
        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => 1, 'unit_id' => 1, 'amount' => -1]],
        ], (new MoveUnitsRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_zero_unit_move_amount_is_rejected(): void
    {
        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => 1, 'unit_id' => 1, 'amount' => 0]],
        ], (new MoveUnitsRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_non_integer_unit_move_amount_is_rejected(): void
    {
        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => 1, 'unit_id' => 1, 'amount' => 'one']],
        ], (new MoveUnitsRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_unknown_unit_id_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();

        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => $kingdom->id, 'unit_id' => 999999, 'amount' => 1]],
        ], (new MoveUnitsRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_source_unit_count_does_not_increase_after_negative_amount(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $sourceKingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::EFFECTS_KINGDOM->value);
        })->update(['skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value]);
        $gameUnit = GameUnit::factory()->create();
        $kingdomUnit = KingdomUnit::factory()->create([
            'kingdom_id' => $sourceKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 10,
        ]);

        $result = resolve(UnitMovementService::class)->moveUnitsToKingdom($character->refresh(), $targetKingdom, [
            'units_to_move' => [[
                'kingdom_id' => $sourceKingdom->id,
                'unit_id' => $kingdomUnit->id,
                'amount' => -5,
            ]],
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertSame(10, $kingdomUnit->refresh()->amount);
    }

    public function test_valid_positive_unit_movement_still_works(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $sourceKingdom = $characterFactory->kingdomManagement()->assignKingdom(['x_position' => 16, 'y_position' => 16])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom(['x_position' => 32, 'y_position' => 32])->getKingdom();
        $character = $characterFactory->getCharacter();
        $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::EFFECTS_KINGDOM->value);
        })->update(['skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value]);
        $gameUnit = GameUnit::factory()->create();
        $kingdomUnit = KingdomUnit::factory()->create([
            'kingdom_id' => $sourceKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 10,
        ]);

        $result = resolve(UnitMovementService::class)->moveUnitsToKingdom($character->refresh(), $targetKingdom, [
            'units_to_move' => [[
                'kingdom_id' => $sourceKingdom->id,
                'unit_id' => $kingdomUnit->id,
                'amount' => 5,
            ]],
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(5, $kingdomUnit->refresh()->amount);
    }
}
