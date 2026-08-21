<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Enums\CraftEventTargetType;
use App\Game\Automation\BatchCrafting\Services\CraftEventTargetService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftEventTargetServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CraftEventTargetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(CraftEventTargetService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_cycle_size_is_the_authoritative_five_target_sequence(): void
    {
        $result = $this->service->cycleSize();

        $this->assertSame(5, $result);
    }

    public function test_resolve_target_returns_the_target_for_the_cycle_position(): void
    {
        $this->assertSame(CraftEventTargetType::WEAPON, $this->service->resolveTarget(0));
        $this->assertSame(CraftEventTargetType::ARMOUR, $this->service->resolveTarget(1));
        $this->assertSame(CraftEventTargetType::RING, $this->service->resolveTarget(2));
        $this->assertSame(CraftEventTargetType::DAMAGE_SPELL, $this->service->resolveTarget(3));
        $this->assertSame(CraftEventTargetType::HEALING_SPELL, $this->service->resolveTarget(4));
    }

    public function test_resolve_target_wraps_the_cycle_position(): void
    {
        $result = $this->service->resolveTarget(5);

        $this->assertSame(CraftEventTargetType::WEAPON, $result);
    }

    public function test_resolve_target_item_returns_null_without_the_matching_crafting_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->resolveTargetItem($character, CraftEventTargetType::WEAPON);

        $this->assertNull($result);
    }

    public function test_resolve_target_item_resolves_the_cheapest_weapon_across_every_subtype(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $expensiveSword = $this->createItem(['name' => 'Expensive Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);
        $cheapDagger = $this->createItem(['name' => 'Cheap Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);

        $result = $this->service->resolveTargetItem($character, CraftEventTargetType::WEAPON);

        $this->assertSame($cheapDagger->id, $result?->id);
        $this->assertNotSame($expensiveSword->id, $result?->id);
    }

    public function test_resolve_target_item_resolves_the_inexpensive_item_for_a_non_weapon_target(): void
    {
        $skill = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $cheapRing = $this->createItem(['name' => 'Cheap Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);

        $result = $this->service->resolveTargetItem($character, CraftEventTargetType::RING);

        $this->assertSame($cheapRing->id, $result?->id);
    }

    public function test_resolve_target_item_returns_null_when_nothing_is_craftable_for_the_target(): void
    {
        $skill = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $result = $this->service->resolveTargetItem($character, CraftEventTargetType::RING);

        $this->assertNull($result);
    }
}
