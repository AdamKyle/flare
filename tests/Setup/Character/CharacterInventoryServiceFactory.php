<?php

namespace Tests\Setup\Character;

use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackTransformer;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryActionService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryComparisonService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryEquipmentService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryFilterService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryReadService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\CharacterInventorySetReadService;
use App\Game\Character\CharacterInventory\Services\CharacterInventorySlotResolver;
use App\Game\Character\CharacterInventory\Services\CharacterUsableInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
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
use App\Game\Gems\Transformers\CharacterGemTransformer;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantManyService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use League\Fractal\Manager;
use Mockery;
use Tests\Setup\Items\ItemTransformerFactory;

class CharacterInventoryServiceFactory
{
    private Manager $manager;

    private ItemEnricherFactory $itemEnricherFactory;

    private EquippableItemTransformer $equippableItemTransformer;

    private InventorySetService $inventorySetService;

    /**
     * Prepare deterministic Character Inventory service collaborators.
     *
     * @return void
     */
    public function __construct()
    {
        $this->manager = new Manager;
        $this->equippableItemTransformer = (new ItemTransformerFactory)->buildEquippableItemTransformer();
        $questItemTransformer = new QuestItemTransformer;
        $this->itemEnricherFactory = new ItemEnricherFactory(
            new EquippableEnricher,
            $this->equippableItemTransformer,
            new UsableItemTransformer,
            $questItemTransformer,
            new PlainDataSerializer,
            $this->manager,
        );
        $this->inventorySetService = new InventorySetService(new SetHandsValidation);
    }

    /**
     * Build the real normal Inventory read service.
     *
     * @return CharacterInventoryReadService
     */
    public function buildReadService(): CharacterInventoryReadService
    {
        return new CharacterInventoryReadService(
            $this->itemEnricherFactory,
            $this->equippableItemTransformer,
            new QuestItemTransformer,
            new InventoryTransformer($this->itemEnricherFactory),
            new CharacterInventoryFilterService,
            new Pagination($this->manager),
            $this->manager,
        );
    }

    /**
     * Build the real Inventory Set read service.
     *
     * @return CharacterInventorySetReadService
     */
    public function buildSetReadService(): CharacterInventorySetReadService
    {
        return new CharacterInventorySetReadService(
            $this->itemEnricherFactory,
            new InventoryTransformer($this->itemEnricherFactory),
            $this->inventorySetService,
            new Pagination($this->manager),
            $this->manager,
            new InventorySetOptionTransformer,
            $this->equippableItemTransformer,
        );
    }

    /**
     * Build the real usable Inventory read service.
     *
     * @return CharacterUsableInventoryService
     */
    public function buildUsableService(): CharacterUsableInventoryService
    {
        return new CharacterUsableInventoryService(
            new ApiUsableItemTransformer,
            new Pagination($this->manager),
            $this->manager,
        );
    }

    /**
     * Build the Inventory slot resolver.
     *
     * @return CharacterInventorySlotResolver
     */
    public function buildSlotResolver(): CharacterInventorySlotResolver
    {
        return new CharacterInventorySlotResolver;
    }

    /**
     * Build the stateless comparison Inventory service.
     *
     * @return CharacterInventoryComparisonService
     */
    public function buildComparisonService(): CharacterInventoryComparisonService
    {
        return new CharacterInventoryComparisonService;
    }

    /**
     * Build the Inventory action service.
     *
     * @param UpdateCharacterSkillsService $updateCharacterSkillsService
     * @param DisenchantService $disenchantService
     * @return CharacterInventoryActionService
     */
    public function buildActionService(
        UpdateCharacterSkillsService $updateCharacterSkillsService,
        DisenchantService $disenchantService,
    ): CharacterInventoryActionService {
        $random = new PhpRandomNumberGenerator;
        $skillBonus = new SkillBonusService(new SkillBonusContextService);

        return new CharacterInventoryActionService(
            new MassDisenchantService(
                new SkillCheckService($random, $skillBonus),
                $random,
                new ChanceCalculator($random),
                $skillBonus,
            ),
            $updateCharacterSkillsService,
            $disenchantService,
        );
    }

    /**
     * Build the Inventory equipment service.
     *
     * @return CharacterInventoryEquipmentService
     */
    public function buildEquipmentService(): CharacterInventoryEquipmentService
    {
        return new CharacterInventoryEquipmentService($this->buildReadService(), $this->buildSetReadService());
    }

    /**
     * Build the real Character Gem Bag service.
     *
     * @return CharacterGemBagService
     */
    public function buildGemBagService(): CharacterGemBagService
    {
        $characterGemTransformer = new CharacterGemTransformer;

        return new CharacterGemBagService(
            $this->manager,
            new PlainDataSerializer,
            new CharacterGemSlotsTransformer($characterGemTransformer),
            new CharacterGemsTransformer($characterGemTransformer),
            new Pagination($this->manager),
        );
    }

    /**
     * Build the real Multi Inventory Action service graph.
     *
     * @param EquipManyBuilder|null $equipManyBuilder
     * @return MultiInventoryActionService
     */
    public function buildMultiInventoryActionService(?EquipManyBuilder $equipManyBuilder = null): MultiInventoryActionService
    {
        $characterInventoryService = $this->buildFacade(
            Mockery::mock(UpdateCharacterSkillsService::class),
            Mockery::mock(DisenchantService::class),
        );
        $shopService = $this->buildShopService($characterInventoryService);

        return new MultiInventoryActionService(
            $this->inventorySetService,
            new EquipItemService($this->manager, new CharacterAttackTransformer, $this->inventorySetService),
            $equipManyBuilder ?? new EquipManyBuilder,
            $shopService,
            $characterInventoryService,
            $this->buildDisenchantManyService(),
            $this->manager,
            new CharacterInventoryCountTransformer,
        );
    }

    /**
     * Build the Character Inventory compatibility facade.
     *
     * @param UpdateCharacterSkillsService $updateCharacterSkillsService
     * @param DisenchantService $disenchantService
     * @return CharacterInventoryService
     */
    public function buildFacade(
        UpdateCharacterSkillsService $updateCharacterSkillsService,
        DisenchantService $disenchantService,
    ): CharacterInventoryService {
        return new CharacterInventoryService(
            $this->buildReadService(),
            $this->buildSetReadService(),
            $this->buildUsableService(),
            $this->buildSlotResolver(),
            $this->buildActionService($updateCharacterSkillsService, $disenchantService),
            $this->buildEquipmentService(),
        );
    }

    /**
     * Build the real Shop service around the supplied Inventory facade.
     *
     * @param CharacterInventoryService $characterInventoryService
     * @return ShopService
     */
    private function buildShopService(CharacterInventoryService $characterInventoryService): ShopService
    {
        return new ShopService(
            new EquipItemService($this->manager, new CharacterAttackTransformer, $this->inventorySetService),
            $characterInventoryService,
            new CharacterInventoryCountTransformer,
            (new ItemTransformerFactory)->buildItemTransformer($this->itemEnricherFactory),
            new Pagination($this->manager),
        );
    }

    /**
     * Build the real bulk Disenchant service.
     *
     * @return DisenchantManyService
     */
    private function buildDisenchantManyService(): DisenchantManyService
    {
        $randomNumberGenerator = new PhpRandomNumberGenerator;
        $skillBonusService = new SkillBonusService(new SkillBonusContextService);

        return new DisenchantManyService(
            new SkillCheckService($randomNumberGenerator, $skillBonusService),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
        );
    }
}
