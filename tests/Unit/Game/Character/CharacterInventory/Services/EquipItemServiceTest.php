<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Values\WeaponTypes;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EquipItemServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?EquipItemService $equipItemService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->equipItemService = resolve(EquipItemService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->equipItemService = null;
    }

    public function test_throw_error_when_item_to_replace_does_not_exist_in_inventory()
    {
        $character = $this->character->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => 87753,
            'position' => 'left-hand',
        ]);

        $this->expectException(EquipItemException::class);

        $equipItemService->replaceItem();
    }

    public function test_inventory_is_full_when_trying_to_replace_inventory_set_item()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
            ]), 75)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->first()->id,
            'position' => 'left-hand',
        ]);

        $this->expectException(EquipItemException::class);

        $equipItemService->replaceItem();
    }

    public function test_cannot_equip_another_unique()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]), 0, 'right-hand', true)
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->first()->id,
            'position' => 'left-hand',
        ]);

        $this->expectException(EquipItemException::class);

        $equipItemService->replaceItem();
    }

    public function test_replace_item_in_set()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'To Replace',
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'Equipped',
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->first()->id,
            'position' => 'left-hand',
        ]);

        $equipItemService->replaceItem();

        $character = $character->refresh();

        $this->assertEquals('Equipped', $character->inventory->slots->first()->item->name);
        $this->assertEquals('To Replace', $character->inventorySets->first()->slots->first()->item->name);
    }

    public function test_cannot_equip_unique_for_non_set()
    {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::WEAPON,
            ]), true, 'left-hand')
            ->giveItem($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]), true, 'right-hand')
            ->giveItem($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]))->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->where('equipped', false)->first()->id,
            'position' => 'left-hand',
        ]);

        $this->expectException(EquipItemException::class);

        $equipItemService->replaceItem();
    }

    public function test_get_unique_item_from_set_by_prefix()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $this->assertEquals(WeaponTypes::WEAPON, $this->equipItemService->getUniqueFromSet(
            $character->inventorySets->first()
        )->item->type);
    }

    public function test_get_unique_item_from_set_by_suffix()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $this->assertEquals(WeaponTypes::WEAPON, $this->equipItemService->getUniqueFromSet(
            $character->inventorySets->first()
        )->item->type);
    }

    public function test_item_is_unique_prefix()
    {
        $this->assertTrue(
            $this->equipItemService->isItemToEquipUnique(
                $this->createItem([
                    'type' => WeaponTypes::WEAPON,
                    'item_prefix_id' => $this->createItemAffix([
                        'randomly_generated' => true,
                        'type' => 'prefix',
                    ])->id,
                ])
            )
        );
    }

    public function test_item_is_unique_suffix()
    {
        $this->assertTrue(
            $this->equipItemService->isItemToEquipUnique(
                $this->createItem([
                    'type' => WeaponTypes::WEAPON,
                    'item_suffix_id' => $this->createItemAffix([
                        'randomly_generated' => true,
                        'type' => 'suffix',
                    ])->id,
                ])
            )
        );
    }

    public function test_item_to_be_replaced_is_unique_prefix()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix',
                ])->id,
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->where('equipped', false)->first()->id,
            'position' => 'right-hand',
        ]);

        $this->assertTrue($equipItemService->isItemToBeReplacedUnique(
            $character->inventorySets->first()
        ));
    }

    public function test_item_to_be_replaced_is_unique_suffix()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->where('equipped', false)->first()->id,
            'position' => 'right-hand',
        ]);

        $this->assertTrue($equipItemService->isItemToBeReplacedUnique(
            $character->inventorySets->first()
        ));
    }

    public function test_unequip_bow()
    {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::BOW,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]), true, 'right-hand')
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand',
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventory,
        );

        $character = $character->refresh();

        $this->assertFalse($character->inventory->slots->first()->equipped);
    }

    public function test_unequip_hammer()
    {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::HAMMER,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]), true, 'right-hand')
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand',
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventory,
        );

        $character = $character->refresh();

        $this->assertFalse($character->inventory->slots->first()->equipped);
    }

    public function test_unequip_stave()
    {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::STAVE,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]), true, 'right-hand')
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand',
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventory,
        );

        $character = $character->refresh();

        $this->assertFalse($character->inventory->slots->first()->equipped);
    }

    public function test_unequip_stave_from_set()
    {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem(
                $this->createItem([
                    'type' => WeaponTypes::STAVE,
                    'item_suffix_id' => $this->createItemAffix([
                        'randomly_generated' => true,
                        'type' => 'suffix',
                    ])->id,
                ])
            )
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::STAVE,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix',
                ])->id,
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand',
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventorySets->first(),
        );

        $character = $character->refresh();

        $this->assertEmpty($character->inventorySets->first()->slots);
        $this->assertCount(2, $character->inventory->slots);
    }
}
