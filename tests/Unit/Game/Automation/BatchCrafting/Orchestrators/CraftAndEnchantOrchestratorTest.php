<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Orchestrators;

use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftAndEnchantOrchestrator;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantOrchestratorTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    public function test_orchestrate_resolves_and_executes_craft_and_enchant_amount_handler(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $character->update(['gold' => 10000, 'inventory_max' => 30]);
        $character = $character->refresh();

        $item = $this->createItem(['name' => 'Orchestrator Enchant Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => -10, 'cost' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = resolve(CraftAndEnchantOrchestrator::class)->orchestrate($batchCrafting, $character);

        $this->assertTrue($result->didCraft());
    }
}
