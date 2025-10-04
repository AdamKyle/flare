<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MassDisenchantServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?MassDisenchantService $massDisenchantService;

    private ?Item $itemToDisenchant;

    private ?GameSkill $enchantingSkill;

    private ?GameSkill $disenchantingSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation();

        $this->massDisenchantService = resolve(MassDisenchantService::class);

        $this->itemToDisenchant = $this->createItem([
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->massDisenchantService = null;
        $this->itemToDisenchant = null;
        $this->disenchantingSkill = null;
        $this->enchantingSkill = null;
    }

    public function test_disenchant_all_items()
    {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $this->massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots->toArray());
    }

    public function test_disenchant_all_items_with_over_flow_of_xp()
    {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $massDisenchantmentService = \Mockery::mock(MassDisenchantService::class)->makePartial();

        $massDisenchantmentService->__construct(resolve(SkillCheckService::class));

        $massDisenchantmentService->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getSkillXp')
            ->andReturn(5000);

        $massDisenchantmentService->setUp($character)->disenchantItems($character->inventory->slots);

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots->toArray());

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
    }

    public function test_get_skill_xp_for_disenchanting_items()
    {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $massDisenchantService = $this->app->make(MassDisenchantService::class);

        $massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
    }

    public function test_get_gold_dust_rush_disenchanting_items()
    {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'name' => 'sample',
                'type' => 'quest',
                'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
            ])
        )->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            }),
        );

        $massDisenchantService = Mockery::mock(MassDisenchantService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('fetchDCRoll')->andReturn(1000);
        });

        $massDisenchantService->__construct(resolve(SkillCheckService::class));

        $massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
    }

    public function test_get_gold_dust_rush_disenchanting_items_does_not_go_abovemax()
    {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'name' => 'sample',
                'type' => 'quest',
                'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
            ])
        )->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            }),
        );

        $massDisenchantService = Mockery::mock(MassDisenchantService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('fetchDCRoll')->andReturn(1000);
        });

        $massDisenchantService->__construct(resolve(SkillCheckService::class));

        $massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
    }

    public function test_get_skill_xp_for_disenchanting_items_when_map_bonus_applied()
    {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->itemToDisenchant, 25)->getCharacter();

        $character->map->gameMap()->update([
            'skill_training_bonus' => .50,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $massDisenchantService = Mockery::mock(MassDisenchantService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('fetchDCRoll')->andReturn(1000);
        });

        $massDisenchantService->__construct(resolve(SkillCheckService::class));

        $massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
    }

    public function test_do_not_go_above_max_gold_dust()
    {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'name' => 'sample',
                'type' => 'quest',
                'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
            ])
        )->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            }),
        );

        $massDisenchantService = Mockery::mock(MassDisenchantService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('fetchDCRoll')->andReturn(1000);
        });

        $massDisenchantService->__construct(resolve(SkillCheckService::class));

        $massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);

        $this->assertGreaterThan(0, $massDisenchantService->getDisenchantingTimesLeveled());
        $this->assertGreaterThan(0, $massDisenchantService->getEnchantingTimesLeveled());
        $this->assertGreaterThan(0, $massDisenchantService->getTotalGoldDust());
    }

    public function test_attempt_gold_dust_rush()
    {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'name' => 'sample',
                'type' => 'quest',
                'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
            ])
        )->giveItemMultipleTimes($this->itemToDisenchant, 10)->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            }),
        );

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $massDisenchantService = resolve(MassDisenchantService::class);

        $massDisenchantService->setUp($character)->disenchantItems($character->inventory->slots);

        $character = $character->refresh();

        $disenchantSkill = $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first();
        $enchantingSkill = $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first();

        $this->assertGreaterThan(1, $disenchantSkill->level);
        $this->assertGreaterThan(1, $enchantingSkill->level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);

        $this->assertGreaterThan(0, $massDisenchantService->getDisenchantingTimesLeveled());
        $this->assertGreaterThan(0, $massDisenchantService->getEnchantingTimesLeveled());
        $this->assertGreaterThan(0, $massDisenchantService->getTotalGoldDust());
    }
}
