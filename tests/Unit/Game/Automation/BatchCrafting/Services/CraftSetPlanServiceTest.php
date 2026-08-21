<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Services\CraftSetPlanService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftSetPlanServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?CraftSetPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();

        $this->service = resolve(CraftSetPlanService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_resolve_plan_returns_a_complete_queue_when_every_required_position_is_valid(): void
    {
        $positions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertEmpty($result['blockers']);
        $this->assertCount(10, $result['queue']);
    }

    public function test_resolve_plan_reports_a_blocker_for_each_missing_required_position(): void
    {
        $result = $this->service->resolvePlan($this->character, []);

        $this->assertCount(10, $result['blockers']);
        $this->assertEmpty($result['queue']);
    }

    public function test_resolve_plan_reports_a_blocker_when_a_selected_item_is_no_longer_craftable(): void
    {
        $body = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolvePlan($this->character, ['body' => 999999]);

        $this->assertNotEmpty($result['blockers']);
        $this->assertNotNull($body);
    }

    public function test_resolve_plan_allows_hands_to_be_omitted(): void
    {
        $positions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertEmpty($result['blockers']);
    }

    public function test_resolve_plan_accepts_a_valid_one_handed_pair(): void
    {
        $positions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'left_hand' => $this->createItem(['name' => 'Set Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'right_hand' => $this->createItem(['name' => 'Set Shield', 'type' => 'shield', 'crafting_type' => 'armour', 'default_position' => 'shield', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertEmpty($result['blockers']);
        $this->assertCount(12, $result['queue']);
    }

    public function test_resolve_plan_rejects_a_two_handed_item_combined_with_another_hand_item(): void
    {
        $positions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'left_hand' => $this->createItem(['name' => 'Set Bow', 'type' => 'bow', 'crafting_type' => 'weapon', 'default_position' => 'bow', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'right_hand' => $this->createItem(['name' => 'Set Shield', 'type' => 'shield', 'crafting_type' => 'armour', 'default_position' => 'shield', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_resolve_plan_rejects_a_non_hand_item_selected_as_a_hand(): void
    {
        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $positions = [
            'body' => $bodyId,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'left_hand' => $bodyId,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_resolve_plan_reports_a_blocker_when_the_character_lacks_the_required_crafting_skill(): void
    {
        $characterWithoutRingSkill = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $ringItem = $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolvePlan($characterWithoutRingSkill, ['ring_0' => $ringItem->id]);

        $this->assertContains('The selected item for the ring_0 position is no longer craftable.', $result['blockers']);
    }

    public function test_resolve_plan_rejects_a_hand_item_when_the_character_lacks_both_weapon_and_armour_skills(): void
    {
        $characterWithoutHandSkills = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $swordItem = $this->createItem(['name' => 'Set Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolvePlan($characterWithoutHandSkills, ['left_hand' => $swordItem->id]);

        $this->assertContains('The selected item for the left_hand position is not a valid hand item.', $result['blockers']);
    }

    public function test_positions_returns_the_deterministic_position_order(): void
    {
        $positions = $this->service->positions();

        $this->assertSame(
            ['left_hand', 'right_hand', 'body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet', 'ring_0', 'ring_1', 'spell-damage', 'spell-healing'],
            $positions
        );
    }

    public function test_resolve_plan_queue_follows_the_authoritative_order_with_no_hands_selected(): void
    {
        $positions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertSame('body', $result['queue'][0]['position']);
        $this->assertSame(
            ['body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet', 'ring_0', 'ring_1', 'spell-damage', 'spell-healing'],
            array_column($result['queue'], 'position')
        );
    }

    public function test_resolve_plan_queue_places_a_single_selected_hand_before_body(): void
    {
        $positions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'left_hand' => $this->createItem(['name' => 'Set Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions);

        $this->assertSame('left_hand', $result['queue'][0]['position']);
        $this->assertSame('body', $result['queue'][1]['position']);
    }

    public function test_resolve_plan_total_cost_sums_the_authoritative_cost_of_every_resolved_item(): void
    {
        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 40, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;

        $result = $this->service->resolvePlan($this->character, ['body' => $bodyId]);

        $this->assertSame(40, $result['total_cost']);
    }
}
