<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Values\CharacterClassValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\EnchantingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EnchantingServiceTest extends TestCase
{

    use RefreshDatabase, CreateItem, CreateClass, CreateGameSkill, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?EnchantingService $enchantingService;

    private ?Item $itemToEnchant;

    private ?ItemAffix $suffix;

    private ?ItemAffix $prefix;

    private ?GameSkill $enchantingSkill;

    public function setUp(): void
    {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING,
        ]);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->enchantingSkill
        )->givePlayerLocation();

        $this->enchantingService = resolve(EnchantingService::class);

        $this->itemToEnchant = $this->createItem([
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $this->suffix = $this->createItemAffix([
            'type'                 => 'suffix',
            'int_required'         => 1,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 2,
            'cost'                 => 1000,
        ]);

        $this->prefix = $this->createItemAffix([
            'type'                 => 'prefix',
            'int_required'         => 1,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 2,
            'cost'                 => 1000,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character          = null;
        $this->enchantingSkill    = null;
        $this->enchantingService  = null;
        $this->suffix             = null;
        $this->itemToEnchant      = null;
    }

    public function testFetchAffixesAndItemsThatCanBeEnchanted() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);
    }

    public function testFetchAffixesAsMerhcant() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT,
        ]))
            ->assignSkill($this->enchantingSkill)
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->itemToEnchant)
            ->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get 15% discount on enchanting items. This discount is applied to the total cost of the enchantments, not the individual enchantments.';
        });
    }

    public function testFetchAffixesAndItemsThatCanBeEnchantedWithAlreadyEnchantedItemAtTheBottom() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->giveItem($this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ]))->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);

        $this->assertArrayHasKey(array_key_last($result['character_inventory']), $result['character_inventory']);
    }

    public function testGetCostOfItemAffixesAsZeroWhenAffixesDoNotExist() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [10000, 100001], 560);

        $this->assertEquals(0, $result);
    }

    public function testGetCostOfItemAffixesAsZeroWhenItemsDoNotExist() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], 560);

        $this->assertEquals(0, $result);
    }

    public function testGetCostOfAffixesToAttach() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(2000, $result);
    }

    public function testGetCostOfAffixesToAttachAsAMerchant() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT,
        ]))->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(floor(2000 - 2000 * 0.15), $result);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get a 15% reduction on enchanting items (reduction applied to total price).';
        });
    }

    public function testGetCostWhenItemHasAffixesAttached() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ])->id);

        $this->assertEquals(4000, $result);
    }
}
