<?php

namespace Tests\Unit\Game\Core\Services;

use App\Flare\Items\Builders\BuildMythicItem;
use App\Flare\Items\Builders\RandomItemDropBuilder;
use App\Flare\Models\Character;
use App\Flare\Values\LocationType;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Services\DropCheckService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;

class DropCheckServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, CreateMonster, RefreshDatabase;

    private ?DropCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(DropCheckService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_process_uses_drop_check_chance_when_not_at_special_location_and_no_game_map_bonus(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->withArgs(function ($passedMonster, $level, $lootingChance, $gameMapBonus) {
                return $level === 1
                    && abs($lootingChance - 0.10) < 0.00001
                    && abs($gameMapBonus - 0.0) < 0.00001
                    && ! is_null($passedMonster)
                    && $passedMonster->id > 0;
            })
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withAnyArgs()
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_quest_items_only_suppresses_ordinary_drop_but_preserves_configured_quest_drops(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => $this->createItem(['type' => 'quest'])->id,
        ]);
        $battleDrop = Mockery::mock(BattleDrop::class);
        $battleDrop->shouldReceive('setMonster')->once()->with($monster)->andReturnSelf();
        $battleDrop->shouldReceive('setSpecialLocation')->once()->with(null)->andReturnSelf();
        $battleDrop->shouldReceive('setManualQuestItemLocation')->once()->with(null)->andReturnSelf();
        $battleDrop->shouldReceive('setGameMapBonus')->once()->with(0.0)->andReturnSelf();
        $battleDrop->shouldReceive('setLootingChance')->once()->with(0.0)->andReturnSelf();
        $battleDrop->shouldReceive('resetRewardTotals')->once()->andReturnSelf();
        $battleDrop->shouldNotReceive('handleDrop');
        $battleDrop->shouldReceive('handleMonsterQuestDrop')->once()->with($character);
        $battleDrop->shouldReceive('handleDelveLocationQuestItems')->once()->with($character);
        $battleDrop->shouldReceive('rewardTotals')->once()->andReturn([
            'auto_sold_gold' => 0,
            'planned_count' => 1,
            'granted_count' => 1,
        ]);

        $result = (new DropCheckService($battleDrop, Mockery::mock(BuildMythicItem::class)))
            ->process($character, $monster, 0.0, true);

        $this->assertSame(1, $result['granted_count']);
    }

    public function test_process_sets_game_map_bonus_when_present(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->withArgs(function ($passedMonster, $level, $lootingChance, $gameMapBonus) {
                return $level === 1
                    && abs($lootingChance - 0.10) < 0.00001
                    && abs($gameMapBonus - 0.25) < 0.00001
                    && ! is_null($passedMonster)
                    && $passedMonster->id > 0;
            })
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withAnyArgs()
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $character->map->gameMap->update([
            'drop_chance_bonus' => 0.25,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_process_handles_king_celestial_mythic_drop_and_clamps_looting_chance_at_point_one_five(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance) {
                return abs($chance - 0.15) < 0.00001;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 1.0);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_process_handles_king_celestial_mythic_drop_without_clamping_when_under_cap(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance) {
                return abs($chance - 0.10) < 0.00001;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_process_awards_mythic_item_in_purgatory_dungeons_when_no_automations(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->withAnyArgs()->zeroOrMoreTimes()->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->withAnyArgs()->zeroOrMoreTimes()->andReturn(1);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.45) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withNoArgs()
            ->andReturnTrue();

        $this->createItemAffix(['type' => 'prefix']);
        $this->createItemAffix(['type' => 'suffix']);

        $this->createItem([
            'specialty_type' => null,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
            'is_mythic' => false,
        ]);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.30);

        $this->createSpecialLocation($character, LocationType::PURGATORY_DUNGEONS->value);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
            'drop_check' => 1,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $character = $character->refresh();
        $afterSlots = $character->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 1, $afterSlots);

        $newItem = $character->inventory->slots()->latest('id')->first()->item;

        $this->assertTrue($newItem->is_mythic);
        $this->assertNotNull($newItem->item_prefix_id);
        $this->assertNotNull($newItem->item_suffix_id);
    }

    public function test_process_does_not_award_mythic_item_in_purgatory_dungeons_when_automations_running(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.45) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.30);

        $this->createSpecialLocation($character, LocationType::PURGATORY_DUNGEONS->value);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
            'drop_check' => 1,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_process_uses_cached_location_with_effect_when_key_does_not_change(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->twice()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.35) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->never();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $specialLocation = $this->createSpecialLocation($character, null);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
            'drop_check' => 1,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $specialLocation->delete();

        $character = $character->refresh()->load('skills.baseSkill', 'map.gameMap', 'currentAutomations');

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_special_location_with_quest_item_can_drop_manual_quest_item(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->andReturnTrue();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => LocationType::SPECIAL->value,
            'enemy_strength_type' => null,
            'name' => 'special_manual_quest_location',
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $this->service?->process($character->refresh(), $monster->refresh());

        $this->assertTrue($character->refresh()->inventory->slots()->where('item_id', $questItem->id)->exists());
    }

    public function test_existing_typed_quest_drop_location_can_still_drop_manual_quest_item(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->twice()
            ->andReturn(false, true);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => LocationType::GOLD_MINES->value,
            'enemy_strength_type' => 1,
            'name' => 'gold_mines_manual_quest_location',
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
            'drop_check' => 1,
        ]);

        $this->service?->process($character->refresh(), $monster->refresh());

        $this->assertTrue($character->refresh()->inventory->slots()->where('item_id', $questItem->id)->exists());
    }

    public function test_cave_of_memories_is_not_handled_by_manual_special_quest_item_path(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->never();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => LocationType::CAVE_OF_MEMORIES->value,
            'enemy_strength_type' => null,
            'name' => 'cave_manual_quest_location',
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $this->service?->process($character->refresh(), $monster->refresh());

        $this->assertFalse($character->refresh()->inventory->slots()->where('item_id', $questItem->id)->exists());
    }

    public function test_location_with_type_but_no_quest_items_does_not_hand_out_quest_item(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->never();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => LocationType::SPECIAL->value,
            'enemy_strength_type' => null,
            'name' => 'special_manual_quest_location_without_items',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $this->assertSame($beforeSlots, $character->refresh()->inventory->slots()->count());
    }

    public function test_enemy_strength_only_location_with_null_type_does_not_hand_out_quest_item(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => null,
            'enemy_strength_type' => 1,
            'name' => 'enemy_strength_only_manual_quest_location',
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
            'drop_check' => 1,
        ]);

        $this->service?->process($character->refresh(), $monster->refresh());

        $this->assertFalse($character->refresh()->inventory->slots()->where('item_id', $questItem->id)->exists());
    }

    public function test_plan_drops_produces_only_one_planned_quest_drop_across_multiple_kills(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->times(3)
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchQuestItemDropCheck')
            ->times(3)
            ->andReturnTrue();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter()->refresh();

        $questItem = $this->createItem([
            'type' => 'quest',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => $questItem->id,
        ]);

        $plan = $this->service?->planDrops($character->refresh(), $monster->refresh(), 3, 0.0);

        $questDrops = array_filter($plan['drops'], function (array $drop) use ($questItem): bool {
            return $drop['item_id'] === $questItem->id;
        });

        $this->assertCount(1, $questDrops);
    }

    public function test_plan_drops_produces_every_normal_drop_even_when_same_item_planned_multiple_times(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->twice()
            ->andReturnTrue();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter()->refresh();

        $drop = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->twice()->andReturn($drop);

        $battleDrop = new BattleDrop($randomItemDropBuilder, Mockery::mock(DisenchantService::class), Mockery::mock(ShopService::class));

        $service = new DropCheckService($battleDrop, resolve(BuildMythicItem::class));

        $plan = $service->planDrops($character->refresh(), $monster->refresh(), 2, 0.0);

        $normalDrops = array_filter($plan['drops'], function (array $plannedDrop): bool {
            return $plannedDrop['source'] === 'monster_drop';
        });

        $this->assertCount(2, $normalDrops);
    }

    public function test_apply_planned_drops_creates_only_one_slot_and_one_message_when_same_quest_item_planned_twice(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter()->refresh();

        $questItem = $this->createItem([
            'type' => 'quest',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $plan = [
            'game_map_bonus' => 0.0,
            'looting_chance' => 0.0,
            'drops' => [
                ['item_id' => $questItem->id, 'is_mythic' => false, 'source' => 'monster_quest_drop'],
                ['item_id' => $questItem->id, 'is_mythic' => false, 'source' => 'monster_quest_drop'],
            ],
        ];

        $this->service?->applyPlannedDrops($character->refresh(), $monster->refresh(), $plan);

        $this->assertSame(1, $character->refresh()->inventory->slots()->where('item_id', $questItem->id)->count());
        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    private function setLootingToBonus(Character $character, float $targetBonus): Character
    {
        $lootingSkill = $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('name', 'Looting');
        })->first();

        if (is_null($lootingSkill)) {
            return $character->refresh()->load('skills.baseSkill', 'map.gameMap', 'currentAutomations');
        }

        $baseSkill = $lootingSkill->baseSkill;

        if (! is_null($baseSkill)) {
            $baseSkill->skill_bonus_per_level = 0.01;
            $baseSkill->max_level = 100;
            $baseSkill->save();
        }

        $perLevel = 0.01;
        $maxLevel = ! is_null($baseSkill) ? (int) $baseSkill->max_level : 100;

        if ($targetBonus >= 1.0) {
            $targetLevel = $maxLevel;
        } else {
            $targetLevel = (int) round(($targetBonus / $perLevel) + 1);

            if ($targetLevel < 1) {
                $targetLevel = 1;
            }

            if ($targetLevel >= $maxLevel) {
                $targetLevel = $maxLevel - 1;
            }
        }

        $lootingSkill->update([
            'level' => $targetLevel,
        ]);

        $character = $character->refresh()->load('skills.baseSkill', 'map.gameMap', 'currentAutomations');

        $computed = $character->skills->where('name', 'Looting')->first()?->skill_bonus ?? 0.0;

        if (abs($computed - $targetBonus) > 0.00001 && $targetBonus < 1.0) {
            throw new \Exception('Looting bonus did not match target. Got: '.$computed.' Target: '.$targetBonus);
        }

        return $character;
    }
}
