<?php

namespace Tests\Unit\Game\PassiveSkills\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\FeatureTypes;
use App\Game\Reincarnate\Services\CharacterReincarnateService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class CharacterReincarnationServiceTest extends TestCase {

    use RefreshDatabase, CreateQuest, CreateNpc;

    private ?CharacterFactory $character;

    private ?CharacterReincarnateService $reincarnationService;

    public function setUp(): void {
        parent::setUp();

        $this->character            = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->reincarnationService = resolve(CharacterReincarnateService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character            = null;
        $this->reincarnationService = null;
    }

    public function testCannotReincarnateWhenQuestNotComplete() {
        $character = $this->character->getCharacter();

        $result    = $this->reincarnationService->reincarnate($character);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You must complete: "The story of rebirth" quest line in Hell first.', $result['message']);
    }

    public function testCannotReincarnateWhenCannotAfford() {
        $character = $this->character->getCharacter();

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
        $character = $this->character->getCharacter();

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
        $character = $this->character->getCharacter();

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
            'level' => 999998,
            'str'   => 999998,
            'dur'   => 999998,
            'dex'   => 999998,
            'chr'   => 999998,
            'int'   => 999998,
            'agi'   => 999998,
            'focus' => 999998,
            'copper_coins' => 100000,
            'reincarnated_stat_increase' => 999999,
        ]);

        $result    = $this->reincarnationService->reincarnate($character->refresh());

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Reincarnated character and applied 20% of your current level (base) stats toward your new (base) stats.', $result['message']);

        $character = $character->refresh();

        $this->assertEquals(50000, $character->copper_coins);
        $this->assertEquals(0.05, $character->xp_penalty);
        $this->assertEquals(1, $character->level);
        $this->assertEquals(1, $character->times_reincarnated);
        $this->assertEquals(999999, $character->str);
        $this->assertGreaterThan(0, $character->reincarnated_stat_increase);
    }

    public function testCannotReincarnateWhenStatsAreMaxed() {
        $character = $this->character->getCharacter();

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
            'level' => 999999,
            'str'   => 999999,
            'dur'   => 999999,
            'dex'   => 999999,
            'chr'   => 999999,
            'int'   => 999999,
            'agi'   => 999999,
            'focus' => 999999,
            'copper_coins' => 100000,
        ]);

        $result    = $this->reincarnationService->reincarnate($character->refresh());

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You have maxed all stats to 999,999.', $result['message']);
    }
}