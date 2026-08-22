<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Orchestrators;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Orchestrators\TrinketryOrchestrator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class TrinketryOrchestratorTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    public function test_orchestrate_resolves_and_executes_the_trinketry_handler(): void
    {
        $trinketSkill = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->createItem(['type' => 'trinket', 'can_craft' => true, 'gold_dust_cost' => 10, 'copper_coin_cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100]);

        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketSkill, 1, false)->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST, 'copper_coins' => CurrencyLimit::MAX_COPPER]);
        $character = $character->refresh();

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience', 'trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = resolve(TrinketryOrchestrator::class)->orchestrate($batchCrafting, $character);

        $this->assertTrue($result->didCraft());
    }
}
