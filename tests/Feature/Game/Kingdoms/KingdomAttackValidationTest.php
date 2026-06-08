<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Requests\AttackRequest;
use App\Game\Kingdoms\Service\KingdomAttackService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomAttackValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_attack_unit_amount_is_rejected(): void
    {
        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => 1, 'unit_id' => 1, 'amount' => -1]],
        ], (new AttackRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_zero_attack_unit_amount_is_rejected(): void
    {
        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => 1, 'unit_id' => 1, 'amount' => 0]],
        ], (new AttackRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_non_integer_attack_unit_amount_is_rejected(): void
    {
        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => 1, 'unit_id' => 1, 'amount' => 'one']],
        ], (new AttackRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_unknown_attack_unit_id_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();

        $validator = Validator::make([
            'units_to_move' => [['kingdom_id' => $kingdom->id, 'unit_id' => 999999, 'amount' => 1]],
        ], (new AttackRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_attacking_unit_count_does_not_increase_after_negative_amount(): void
    {
        $attackerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $sourceKingdom = $attackerFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $attacker = $attackerFactory->getCharacter();
        $attacker->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::EFFECTS_KINGDOM->value);
        })->update(['skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value]);
        $defenderFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $targetKingdom = $defenderFactory->kingdomManagement()->assignKingdom(['game_map_id' => $sourceKingdom->game_map_id, 'protected_until' => null])->getKingdom();
        $gameUnit = GameUnit::factory()->create();
        $kingdomUnit = KingdomUnit::factory()->create([
            'kingdom_id' => $sourceKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 10,
        ]);

        $result = resolve(KingdomAttackService::class)->attackKingdom($attacker->refresh(), $targetKingdom, [
            'units_to_move' => [[
                'kingdom_id' => $sourceKingdom->id,
                'unit_id' => $kingdomUnit->id,
                'amount' => -5,
            ]],
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertSame(10, $kingdomUnit->refresh()->amount);
    }

    public function test_valid_positive_attack_setup_still_proceeds(): void
    {
        Queue::fake();
        Event::fake();

        $attackerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $sourceKingdom = $attackerFactory->kingdomManagement()->assignKingdom(['x_position' => 16, 'y_position' => 16])->getKingdom();
        $attacker = $attackerFactory->getCharacter();
        $attacker->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::EFFECTS_KINGDOM->value);
        })->update(['skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value]);
        $defenderFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $targetKingdom = $defenderFactory->kingdomManagement()->assignKingdom([
            'game_map_id' => $sourceKingdom->game_map_id,
            'x_position' => 32,
            'y_position' => 32,
            'protected_until' => null,
        ])->getKingdom();
        $gameUnit = GameUnit::factory()->create();
        $kingdomUnit = KingdomUnit::factory()->create([
            'kingdom_id' => $sourceKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 10,
        ]);

        $result = resolve(KingdomAttackService::class)->attackKingdom($attacker->refresh(), $targetKingdom, [
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
