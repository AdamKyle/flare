<?php

namespace Tests\Feature\Game\Reincarnation\Controllers\Api;

use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\FeatureTypes;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class ReincarnateControllerTest extends TestCase
{
    use CreateItem, CreateNpc, CreateQuest, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_reincarnate()
    {

        MaxLevelConfiguration::create([
            'max_level' => 2000,
            'half_way' => 1000,
            'three_quarters' => 1500,
            'last_leg' => 1900,
        ]);

        $item = $this->createItem(['effect' => ItemEffectsValue::CONTINUE_LEVELING]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update(['level' => 2000]);

        $quest = $this->createQuest([
            'unlocks_feature' => FeatureTypes::REINCARNATION,
            'npc_id' => $this->createNpc()->id,
        ]);

        $character->questsCompleted()->create([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $character = $character->refresh();

        $character->update([
            'level' => 4800,
            'str' => 4700,
            'dur' => 4700,
            'dex' => 4700,
            'chr' => 4700,
            'int' => 4700,
            'agi' => 4700,
            'focus' => 4700,
            'copper_coins' => 100000,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/reincarnate/'.$character->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(
            'Reincarnated character and applied 20% of your current level (base) stats toward your new (base) stats.',
            $jsonData['message']
        );
    }
}
