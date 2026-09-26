<?php

namespace Tests\Unit\Game\Market\Services;

use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer as ApiUsableItemTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use App\Game\Core\Values\ValidEquipPositionsValue;
use App\Game\Gems\Services\GemComparison;
use App\Game\Gems\Services\ItemAtonements;
use App\Game\Market\Services\MarketBoard;
use App\Game\Market\Services\MarketRealtimePublisher;
use App\Game\Market\Transformers\MarketItemsTransformer;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MarketBoardBatchCraftingTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?MarketBoard $marketBoard;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = new Manager;
        $plainDataSerializer = new PlainDataSerializer;
        $randomNumberGenerator = new PhpRandomNumberGenerator;
        $equippableItemTransformer = new EquippableItemTransformer;
        $questItemTransformer = new QuestItemTransformer;
        $apiUsableItemTransformer = new ApiUsableItemTransformer;
        $inventorySetService = new InventorySetService(new SetHandsValidation);
        $skillBonusService = new SkillBonusService(new SkillBonusContextService);
        $equipItemService = new EquipItemService($manager, new CharacterAttackTransformer, $inventorySetService);

        $itemEnricherFactory = new ItemEnricherFactory(
            new EquippableEnricher,
            $equippableItemTransformer,
            new UsableItemTransformer,
            $questItemTransformer,
            $plainDataSerializer,
            $manager,
        );

        $comparisonService = new ComparisonService(
            new ValidEquipPositionsValue,
            new CharacterInventoryService(
                $itemEnricherFactory,
                $equippableItemTransformer,
                $questItemTransformer,
                $apiUsableItemTransformer,
                new InventoryTransformer($itemEnricherFactory),
                $inventorySetService,
                new MassDisenchantService(new SkillCheckService($randomNumberGenerator, $skillBonusService), $randomNumberGenerator, new ChanceCalculator($randomNumberGenerator), $skillBonusService),
                Mockery::mock(UpdateCharacterSkillsService::class),
                Mockery::mock(DisenchantService::class),
                new Pagination($manager),
                $manager,
                new InventorySetOptionTransformer,
            ),
            $equipItemService,
            new ItemAtonements(new GemComparison(new CharacterGemsTransformer, $plainDataSerializer, $manager)),
            $manager,
            $equippableItemTransformer,
            $apiUsableItemTransformer,
        );

        $this->marketBoard = new MarketBoard(
            $equipItemService,
            $comparisonService,
            new MarketRealtimePublisher($manager, new MarketItemsTransformer(new ItemTransformer($itemEnricherFactory))),
            new CharacterInventoryCountTransformer,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->marketBoard = null;
    }

    public function test_list_batch_crafted_item_creates_a_market_listing_at_the_requested_price(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();

        $this->marketBoard->listBatchCraftedItem($character, $item, 5000);

        $listing = MarketBoardModel::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertNotNull($listing);
        $this->assertSame(5000, $listing->listed_price);
    }

    public function test_list_batch_crafted_item_clamps_to_the_maximum_gold_currency_limit(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();

        $this->marketBoard->listBatchCraftedItem($character, $item, CurrencyLimit::MAX_GOLD + 1000);

        $listing = MarketBoardModel::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertSame(CurrencyLimit::MAX_GOLD, $listing->listed_price);
    }
}
