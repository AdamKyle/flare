<?php

namespace Tests\Unit\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class RandomEnchantmentServiceTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?RandomEnchantmentService $randomEnchantmentService;

    protected function setUp(): void
    {

        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->randomEnchantmentService = resolve(RandomEnchantmentService::class);

        // For the generating of the item with affixes attached
        $this->createItem(['type' => 'weapon']);
    }

    protected function tearDown(): void
    {

        parent::tearDown();

        $this->character = null;
        $this->randomEnchantmentService = null;
    }

    public function test_generate_item_for_each_type()
    {
        $character = $this->character->getCharacter();

        $basicItem = $this->randomEnchantmentService->generateForType($character, 'basic');
        $mediumItem = $this->randomEnchantmentService->generateForType($character, 'medium');
        $legendaryItem = $this->randomEnchantmentService->generateForType($character, 'legendary');
        $default = $this->randomEnchantmentService->generateForType($character, '');

        $this->assertInstanceOf(Item::class, $basicItem);
        $this->assertInstanceOf(Item::class, $mediumItem);
        $this->assertInstanceOf(Item::class, $legendaryItem);
        $this->assertInstanceOf(Item::class, $default);
    }

    public function test_generate_with_both_suffix_and_prefix()
    {
        $character = $this->character->getCharacter();

        $randomEnchantmentService = \Mockery::mock(RandomEnchantmentService::class)->makePartial();

        $randomEnchantmentService->__construct(resolve(RandomAffixGenerator::class));

        $randomEnchantmentService->shouldAllowMockingProtectedMethods()
            ->shouldReceive('shouldAddSuffixToItem')
            ->andReturn(100);

        $basicItem = $randomEnchantmentService->generateForType($character);

        $this->assertNotNull($basicItem->item_suffix_id);
        $this->assertNotNull($basicItem->item_prefix_id);
    }

    public function test_get_cost_for_each_type()
    {
        $cost = $this->randomEnchantmentService->getCost();

        $this->assertEquals(RandomAffixDetails::LEGENDARY, $cost);
    }

    public function test_fetch_all_unique_items()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['randomly_generated' => true])->id,
        ]))->giveItem($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['randomly_generated' => true])->id,
        ]))->getCharacter();

        $this->assertCount(2, $this->randomEnchantmentService->fetchUniquesFromCharactersInventory($character));
    }

    public function test_fetch_data_for_api_call()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['randomly_generated' => true])->id,
        ]))->giveItem($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['randomly_generated' => true])->id,
        ]))->giveItem($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['randomly_generated' => false])->id,
        ]))->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['randomly_generated' => false])->id,
        ]))->getCharacter();

        $data = $this->randomEnchantmentService->fetchDataForApi($character);

        $this->assertCount(2, $data['unique_slots']);
        $this->assertCount(2, $data['non_unique_slots']);
    }

    public function test_fetch_non_unique_items()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['randomly_generated' => false])->id,
        ]))->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['randomly_generated' => false])->id,
        ]))->getCharacter();

        $data = $this->randomEnchantmentService->fetchNonUniqueItems($character);

        $this->assertCount(2, $data);
    }
}
