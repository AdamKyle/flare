<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Orchestrators;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftingOrchestrator;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftingOrchestratorTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    public function test_orchestrate_resolves_and_executes_craft_amount_handler_for_specific_item_mode(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Orchestrator Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $result = resolve(CraftingOrchestrator::class)->orchestrate($batchCrafting, $character);

        $this->assertTrue($result->didCraft());
    }

    public function test_orchestrate_throws_clearly_for_an_unregistered_craft_mode(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        resolve(CraftingOrchestrator::class)->orchestrate($batchCrafting, $character);
    }
}
