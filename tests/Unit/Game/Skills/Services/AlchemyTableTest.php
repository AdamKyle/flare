<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Item;
use App\Game\Skills\Services\AlchemyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class AlchemyTableTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?Item $alchemyItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->alchemyItem = $this->createItem([
            'gold_dust_cost' => 1000,
            'shards_cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->alchemyItem = null;
    }

    public function test_fetch_alchemist_items_includes_owned_amount_from_alchemy_bag(): void
    {
        $character = $this->character->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->alchemyItem->id,
            'amount' => 7,
        ]);

        $alchemyService = resolve(AlchemyService::class);
        $items = $alchemyService->fetchAlchemistItems($character->refresh());

        $found = $items->firstWhere('id', $this->alchemyItem->id);

        $this->assertNotNull($found);
        $this->assertEquals(7, $found->owned_amount);
    }

    public function test_fetch_alchemist_items_shows_zero_owned_when_not_in_alchemy_bag(): void
    {
        $character = $this->character->getCharacter();

        $alchemyService = resolve(AlchemyService::class);
        $items = $alchemyService->fetchAlchemistItems($character);

        $found = $items->firstWhere('id', $this->alchemyItem->id);

        $this->assertNotNull($found);
        $this->assertEquals(0, $found->owned_amount);
    }

    public function test_fetch_alchemist_items_reflects_stacked_amount(): void
    {
        $character = $this->character->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->alchemyItem->id,
            'amount' => 3,
        ]);

        $alchemyService = resolve(AlchemyService::class);
        $items = $alchemyService->fetchAlchemistItems($character->refresh());

        $found = $items->firstWhere('id', $this->alchemyItem->id);

        $this->assertEquals(3, $found->owned_amount);
    }
}
