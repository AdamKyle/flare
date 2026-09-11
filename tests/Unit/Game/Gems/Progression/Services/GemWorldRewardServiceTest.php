<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\GemProgression\GemWorldRewardTestFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleRewardRequestStep;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateCharacterGameMapGemScroll;
use Tests\Traits\CreateItem;

class GemWorldRewardServiceTest extends TestCase
{
    use CreateCharacterBattleRewardRequestStep,
        CreateCharacterGameMapGemProgression,
        CreateCharacterGameMapGemScroll,
        CreateItem,
        RefreshDatabase;

    private GemWorldRewardTestFactory $gemWorldRewardTestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemWorldRewardTestFactory = new GemWorldRewardTestFactory;
    }

    public function test_returns_no_op_when_character_is_not_in_a_generated_gem_world(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $character->id]);

        $result = $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertFalse($result['applied']);
    }

    public function test_map_gem_world_awards_global_and_personal_progression_xp(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertDatabaseHas('game_map_gem_progressions', [
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 1,
            'xp' => 105,
        ]);

        $this->assertDatabaseHas('character_game_map_gem_progressions', [
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 1,
            'xp' => 105,
        ]);
    }

    public function test_location_gem_world_awards_its_exact_location_profile_progression(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedLocationGemWorldCharacter();
        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertDatabaseHas('game_location_gem_progressions', [
            'game_location_gem_paramter_id' => $graph->locationProfile->id,
            'xp' => 105,
        ]);

        $this->assertDatabaseHas('character_game_location_gem_progressions', [
            'character_id' => $graph->character->id,
            'game_location_gem_paramter_id' => $graph->locationProfile->id,
            'xp' => 105,
        ]);
    }

    public function test_base_gem_xp_equals_effective_monster_xp_plus_five_percent_times_kills(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $graph->character, ['xp' => 200, 'gold' => 10], 3, []);

        $this->assertDatabaseHas('game_map_gem_progressions', [
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'xp' => 630,
        ]);
    }

    public function test_xp_scroll_modifies_personal_xp_only_and_never_global_xp(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $scrollItem = $this->createGemXpScrollItem(0.20);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $scrollItem->id,
        ]);

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertDatabaseHas('game_map_gem_progressions', [
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'xp' => 105,
        ]);

        $this->assertDatabaseHas('character_game_map_gem_progressions', [
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'xp' => 126,
        ]);
    }

    public function test_personal_level_below_one_hundred_never_rolls_a_scroll_reward(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertSame(0, AlchemyBagSlot::where('alchemy_bag_id', $graph->character->alchemyBag->id)->count());
    }

    public function test_personal_level_at_least_one_hundred_rolls_one_scroll_chance_per_kill(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 0,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 3, []);

        $this->assertSame(3, AlchemyBagSlot::where('alchemy_bag_id', $graph->character->alchemyBag->id)->count());
    }

    public function test_successful_scroll_roll_places_one_generated_item_in_the_alchemy_bag(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 0,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $slot = AlchemyBagSlot::where('alchemy_bag_id', $graph->character->alchemyBag->id)->first();

        $this->assertNotNull($slot);
        $this->assertTrue($slot->item->randomly_generated);
        $this->assertNotNull($slot->item->gem_scroll_type);
    }

    public function test_full_alchemy_bag_loses_the_scroll_reward_without_overflow(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $graph->character->update(['alchemy_bag_limit' => 0]);
        $character = $graph->character->refresh();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 0,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertSame(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->count());
    }

    public function test_resuming_the_same_step_does_not_duplicate_global_or_personal_xp_or_scrolls(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 0,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);
        $service = $this->gemWorldRewardTestFactory->buildService($chanceCalculator);

        $service->applyToLedgerStep($step, $graph->character->fresh(), ['xp' => 100, 'gold' => 10], 1, []);

        $step = $step->fresh();

        $service->applyToLedgerStep($step, $graph->character->fresh(), ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertDatabaseHas('game_map_gem_progressions', [
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'xp' => 105,
        ]);

        $this->assertSame(1, AlchemyBagSlot::where('alchemy_bag_id', $graph->character->alchemyBag->id)->count());
    }

    public function test_currency_scroll_only_affects_its_selected_currency(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $graph->character->update(['gold' => 0, 'shards' => 0]);
        $character = $graph->character->refresh();

        $goldScroll = $this->createGemCurrencyScrollItem(0.50, GemScrollCurrencyType::GOLD);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $goldScroll->id,
        ]);

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $character->id]);

        $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $character, ['xp' => 100, 'gold' => 100], 1, ['gold' => 100, 'shards' => 50]);

        $character = $character->fresh();

        $this->assertSame(50, $character->gold);
        $this->assertSame(0, $character->shards);
    }

    public function test_currency_scroll_bonus_is_capped_at_the_existing_currency_limit(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $graph->character->update(['gold' => CurrencyLimit::MAX_GOLD - 10]);
        $character = $graph->character->refresh();

        $goldScroll = $this->createGemCurrencyScrollItem(1.0, GemScrollCurrencyType::GOLD);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $goldScroll->id,
        ]);

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $character->id]);

        $this->gemWorldRewardTestFactory->buildService()->applyToLedgerStep($step, $character, ['xp' => 100, 'gold' => 1000], 1, ['gold' => 1000]);

        $character = $character->fresh();

        $this->assertSame(CurrencyLimit::MAX_GOLD, $character->gold);
    }

    public function test_personal_level_seven_hundred_delivers_enhanced_equipment_to_inventory(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 700,
            'xp' => 0,
        ]);

        $this->createItem(['type' => 'weapon']);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $character = $graph->character->fresh();
        $slot = $character->inventory->slots()->latest('id')->first();

        $this->assertNotNull($slot);
        $this->assertGreaterThanOrEqual(1, $slot->item->socket_count);
        $this->assertLessThanOrEqual(6, $slot->item->socket_count);
        $this->assertTrue($slot->item->has_gems_socketed);
        $this->assertGreaterThanOrEqual(1, $slot->item->sockets()->count());
        $this->assertLessThanOrEqual($slot->item->socket_count, $slot->item->sockets()->count());
    }

    public function test_full_inventory_loses_the_enhanced_equipment_reward_without_overflow(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $graph->character->update(['inventory_max' => 0]);
        $character = $graph->character->refresh();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 700,
            'xp' => 0,
        ]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertSame(0, $character->fresh()->inventory->slots()->count());
    }

    public function test_item_scroll_opportunity_delivers_a_rarity_item_with_sockets_and_gems(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 0,
        ]);

        $itemScroll = $this->createGemItemScrollItem(1.0, 0.10, 0.05);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $itemScroll->id,
        ]);

        $this->createItem(['type' => 'weapon']);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->with(2.0)->andReturn(false);
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $character = $graph->character->fresh();
        $slot = $character->inventory->slots()->latest('id')->first();

        $this->assertNotNull($slot);
        $this->assertTrue($slot->item->has_gems_socketed);
        $this->assertGreaterThanOrEqual(1, $slot->item->sockets()->count());
    }

    public function test_scroll_drop_chance_is_fixed_at_two_percent_regardless_of_item_scroll_bonus(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 0,
        ]);

        $itemScroll = $this->createGemItemScrollItem(5.0);
        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $itemScroll->id,
        ]);

        $this->createItem(['type' => 'weapon']);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('passesPercentage')->with(2.0)->once()->andReturn(false);
            $mock->shouldReceive('passesPercentage')->andReturn(true);
        });

        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $this->gemWorldRewardTestFactory->buildService($chanceCalculator)->applyToLedgerStep($step, $graph->character, ['xp' => 100, 'gold' => 10], 1, []);

        $this->assertSame(0, AlchemyBagSlot::where('alchemy_bag_id', $graph->character->alchemyBag->id)->count());
    }
}
