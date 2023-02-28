<?php

namespace Tests\Unit\Game\Reincarnation\Services;

use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\FeatureTypes;
use App\Game\Reincarnate\Services\CharacterReincarnateService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class CharacterReincarnationServiceTest extends TestCase {

    use RefreshDatabase, CreateQuest, CreateNpc, CreateItem;

    private ?CharacterFactory $character;

    private ?CharacterReincarnateService $reincarnationService;

    public function setUp(): void {
        parent::setUp();

        $this->character            = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->reincarnationService = resolve(CharacterReincarnateService::class);

        MaxLevelConfiguration::create([
            'max_level'      => 2000,
            'half_way'       => 1000,
            'three_quarters' => 1500,
            'last_leg'       => 1900,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character            = null;
        $this->reincarnationService = null;
    }

    public function testCannotReincarnateWhenCannotLevelToMax() {
        $character = $this->character->getCharacter();

        $result    = $this->reincarnationService->reincarnate($character);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You need to complete the quest: Reach for the stars (Labyrinth, one off quests) to be able to reincarnate', $result['message']);
    }

    public function testCannotReincarnateWhenCannotLevelToMaxWhenNotMaxLevel() {
        $item      = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result    = $this->reincarnationService->reincarnate($character);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You must be at max level to reincarnate.', $result['message']);
    }

    public function testCannotReincarnateWhenQuestNotComplete() {
        $item      = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2000]);

        $character->refresh();

        $result    = $this->reincarnationService->reincarnate($character);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You must complete: "The story of rebirth" quest line in Hell first.', $result['message']);
    }

    public function testCannotReincarnateWhenCannotAfford() {
        $item      = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2000]);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureTypes::REINCARNATION,
            'npc_id'          => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        $character = $character->refresh();

        $result    = $this->reincarnationService->reincarnate($character);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Reincarnation costs 50,000 Copper Coins', $result['message']);
    }

    public function testCanReincarnate() {
        $item      = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2000]);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureTypes::REINCARNATION,
            'npc_id'          => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        $character = $character->refresh();

        $character->update([
            'level' => 4800,
            'str'   => 4700,
            'dur'   => 4700,
            'dex'   => 4700,
            'chr'   => 4700,
            'int'   => 4700,
            'agi'   => 4700,
            'focus' => 4700,
            'copper_coins' => 100000,
        ]);

        $result    = $this->reincarnationService->reincarnate($character->refresh());

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Reincarnated character and applied 20% of your current level (base) stats toward your new (base) stats.', $result['message']);

        $character = $character->refresh();

        $this->assertEquals(50000, $character->copper_coins);
        $this->assertEquals(0.05, $character->xp_penalty);
        $this->assertEquals(1, $character->level);
        $this->assertEquals(1, $character->times_reincarnated);
        $this->assertGreaterThan(0, $character->reincarnated_stat_increase);
    }

    public function testReincarnationWillNotGoAboveMaxValue() {
        $item      = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2000]);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureTypes::REINCARNATION,
            'npc_id'          => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        $character = $character->refresh();

        $character->update([
            'level' => 99999999998,
            'str'   => 99999999998,
            'dur'   => 99999999998,
            'dex'   => 99999999998,
            'chr'   => 99999999998,
            'int'   => 99999999998,
            'agi'   => 99999999998,
            'focus' => 99999999998,
            'copper_coins' => 100000,
            'reincarnated_stat_increase' => 99999999999,
        ]);

        $result    = $this->reincarnationService->reincarnate($character->refresh());

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Reincarnated character and applied 20% of your current level (base) stats toward your new (base) stats.', $result['message']);

        $character = $character->refresh();

        $this->assertEquals(50000, $character->copper_coins);
        $this->assertEquals(0.05, $character->xp_penalty);
        $this->assertEquals(1, $character->level);
        $this->assertEquals(1, $character->times_reincarnated);
        $this->assertEquals(99999999999, $character->str);
        $this->assertGreaterThan(0, $character->reincarnated_stat_increase);
    }

    public function testCannotReincarnateWhenStatsAreMaxed() {
        $item      = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2000]);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureTypes::REINCARNATION,
            'npc_id'          => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id'     => $quest->id,
        ]);

        $character = $character->refresh();

        $character->update([
            'level' => 99999999999,
            'str'   => 99999999999,
            'dur'   => 99999999999,
            'dex'   => 99999999999,
            'chr'   => 99999999999,
            'int'   => 99999999999,
            'agi'   => 99999999999,
            'focus' => 99999999999,
            'copper_coins' => 100000,
        ]);

        $result    = $this->reincarnationService->reincarnate($character->refresh());

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You have maxed all stats to 99,999,999,999.', $result['message']);
    }
}
