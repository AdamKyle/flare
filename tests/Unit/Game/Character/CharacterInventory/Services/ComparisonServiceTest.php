<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer as ApiUsableItemTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Core\Values\ValidEquipPositionsValue;
use App\Game\Gems\Services\GemComparison;
use App\Game\Gems\Services\ItemAtonements;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ComparisonServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ComparisonService $comparisonService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $manager = new Manager;
        $plainDataSerializer = new PlainDataSerializer;
        $randomNumberGenerator = new PhpRandomNumberGenerator;
        $equippableItemTransformer = new EquippableItemTransformer;
        $questItemTransformer = new QuestItemTransformer;
        $apiUsableItemTransformer = new ApiUsableItemTransformer;
        $inventorySetService = new InventorySetService(new SetHandsValidation);

        $itemEnricherFactory = new ItemEnricherFactory(
            new EquippableEnricher,
            $equippableItemTransformer,
            new UsableItemTransformer,
            $questItemTransformer,
            $plainDataSerializer,
            $manager,
        );

        $this->comparisonService = new ComparisonService(
            new ValidEquipPositionsValue,
            new CharacterInventoryService(
                $itemEnricherFactory,
                $equippableItemTransformer,
                $questItemTransformer,
                $apiUsableItemTransformer,
                new InventoryTransformer($itemEnricherFactory),
                $inventorySetService,
                new MassDisenchantService(new SkillCheckService($randomNumberGenerator), $randomNumberGenerator, new ChanceCalculator($randomNumberGenerator)),
                Mockery::mock(UpdateCharacterSkillsService::class),
                Mockery::mock(DisenchantService::class),
                new Pagination($manager),
                $manager,
                new InventorySetOptionTransformer,
            ),
            new EquipItemService($manager, new CharacterAttackTransformer, $inventorySetService),
            new ItemAtonements(new GemComparison(new CharacterGemsTransformer, $plainDataSerializer, $manager)),
            $manager,
            $equippableItemTransformer,
            $apiUsableItemTransformer,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;

        $this->comparisonService = null;
    }

    public function test_item_comparison_details_is_empty_when_nothing_equipped()
    {
        $item = $this->createItem(['type' => ItemType::WAND->value]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertEmpty($comparisonData['details']);
    }

    public function test_item_comparison_details_is_empty_when_something_equipped_but_comparing_for_quest()
    {
        $item = $this->createItem(['type' => 'quest']);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]),
            true,
            'left-hand'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertEmpty($comparisonData['details']);
        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['name']);
    }

    public function test_item_comparison_details_is_empty_when_something_equipped_but_comparing_for_usable_item()
    {
        $item = $this->createItem(['type' => 'alchemy']);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => ItemType::WAND->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]),
            true,
            'left-hand'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertEmpty($comparisonData['details']);
        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['affix_name']);
    }

    public function test_item_comparison_details_is_not_empty_when_something_equipped()
    {
        $item = $this->createItem(['type' => ItemType::SWORD->value]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]),
            true,
            'left-hand'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_trinket_comparison_details_is_not_empty_when_trinket_equipped()
    {
        $item = $this->createItem([
            'type' => 'trinket',
            'str_mod' => 0.25,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => 'trinket',
                'str_mod' => 0.10,
            ]),
            true,
            'trinket'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertNotEmpty($comparisonData['details']);
        $this->assertEquals('trinket', $comparisonData['details'][0]['position']);
    }

    public function test_unsupported_comparison_type_returns_empty_details()
    {
        $item = $this->createItem(['type' => 'artifact']);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => 'artifact',
                'str_mod' => 0.10,
            ]),
            true,
            'artifact'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertEmpty($comparisonData['details']);
    }

    public function test_item_comparison_uses_equipped_set_index_when_comparing_against_an_equipped_set()
    {
        $item = $this->createItem(['type' => ItemType::SWORD->value]);

        $manager = $this->character->inventorySetManagement()
            ->createInventorySets(2)
            ->putItemInSet($this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]), 1, 'left-hand', true);

        $character = $manager->getCharacterFactory()->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot);

        $this->assertNotEmpty($comparisonData['details']);
        $this->assertTrue($comparisonData['setEquipped']);
        $this->assertSame(2, $comparisonData['setIndex']);
    }

    public function test_build_shop_data_for_bow()
    {
        $item = $this->createItem(['type' => ItemType::BOW->value]);

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]),
            true,
            'left-hand'
        )->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item);

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_build_shop_data_for_armour_type()
    {
        $item = $this->createItem(['type' => ArmourType::SHIELD->value]);

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type' => ArmourType::SHIELD->value,
                'base_ac' => 25,
                'str_mod' => 0.10,
            ]),
            true,
            'left-hand'
        )->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item);

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_build_shop_data_for_spell()
    {
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type' => ItemType::SPELL_HEALING->value,
                'base_healing' => 25,
                'str_mod' => 0.10,
            ]),
            true,
            'spell-one'
        )->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item);

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_build_shop_data_for_spell_in_equipped_set()
    {
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);

        $manager = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($this->createItem([
                'type' => ItemType::SPELL_HEALING->value,
                'base_healing' => 25,
                'str_mod' => 0.10,
            ]), 0, 'spell-one', true);

        $character = $manager->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item);

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }
}
