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

class EquipItemServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?EquipItemService $equipItemService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->equipItemService = resolve(EquipItemService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->equipItemService = null;
    }

    public function testThrowErrorWhenItemToReplaceDoesNotExistInInventory() {
        $character = $this->character->getCharacter();

        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => 87753,
            'position' => 'left-hand',
        ]);

        $this->expectException(EquipItemException::class);

        $equipItemService->replaceItem();
    }

    public function testInventoryIsFullWhenTryingToReplaceInventorySetItem() {
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

    public function testCannotEquipAnotherUnique() {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix'
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
                    'type' => 'prefix'
                ])->id
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

    public function testReplaceItemInSet() {
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
                'name' => 'Equipped'
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

    public function testCannotEquipUniqueForNonSet() {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::WEAPON,
            ]), true, 'left-hand')
            ->giveItem($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix'
                ])->id
            ]), true, 'right-hand')
            ->giveItem($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix'
                ])->id,
            ]))->getCharacter();


        $equipItemService = $this->equipItemService->setCharacter($character)->setRequest([
            'slot_id' => $character->inventory->slots->where('equipped', false)->first()->id,
            'position' => 'left-hand',
        ]);

        $this->expectException(EquipItemException::class);

        $equipItemService->replaceItem();
    }

    public function testGetUniqueItemFromSetByPrefix() {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix'
                ])->id
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $this->assertEquals(WeaponTypes::WEAPON, $this->equipItemService->getUniqueFromSet(
            $character->inventorySets->first()
        )->item->type);
    }

    public function testGetUniqueItemFromSetBySuffix() {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $this->assertEquals(WeaponTypes::WEAPON, $this->equipItemService->getUniqueFromSet(
            $character->inventorySets->first()
        )->item->type);
    }

    public function testItemIsUniquePrefix() {
        $this->assertTrue(
            $this->equipItemService->isItemToEquipUnique(
                $this->createItem([
                    'type' => WeaponTypes::WEAPON,
                    'item_prefix_id' => $this->createItemAffix([
                        'randomly_generated' => true,
                        'type' => 'prefix'
                    ])->id
                ])
            )
        );
    }

    public function testItemIsUniqueSuffix() {
        $this->assertTrue(
            $this->equipItemService->isItemToEquipUnique(
                $this->createItem([
                    'type' => WeaponTypes::WEAPON,
                    'item_suffix_id' => $this->createItemAffix([
                        'randomly_generated' => true,
                        'type' => 'suffix'
                    ])->id
                ])
            )
        );
    }

    public function testItemToBeReplacedIsUniquePrefix() {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix'
                ])->id,
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_prefix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'prefix'
                ])->id
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

    public function testItemToBeReplacedIsUniqueSuffix() {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id,
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id
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

    public function testUnequipBow() {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::BOW,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id
            ]), true, 'right-hand')
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand'
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventory,
        );

        $character = $character->refresh();

        $this->assertFalse($character->inventory->slots->first()->equipped);
    }

    public function testUnequipHammer() {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::HAMMER,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id
            ]), true, 'right-hand')
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand'
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventory,
        );

        $character = $character->refresh();

        $this->assertFalse($character->inventory->slots->first()->equipped);
    }

    public function testUnequipStave() {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => WeaponTypes::STAVE,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id
            ]), true, 'right-hand')
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand'
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventory,
        );

        $character = $character->refresh();

        $this->assertFalse($character->inventory->slots->first()->equipped);
    }

    public function testUnequipStaveFromSet() {
        $character = $this->character
            ->inventoryManagement()
            ->giveItem(
                $this->createItem([
                    'type' => WeaponTypes::STAVE,
                    'item_suffix_id' => $this->createItemAffix([
                        'randomly_generated' => true,
                        'type' => 'suffix'
                    ])->id
                ])
            )
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::STAVE,
                'item_suffix_id' => $this->createItemAffix([
                    'randomly_generated' => true,
                    'type' => 'suffix'
                ])->id
            ]), 0, 'right-hand', true)
            ->getCharacter();

        $this->equipItemService->setCharacter($character)->setRequest([
            'position' => 'right-hand'
        ])->unequipSlot(
            $character->inventory->slots->first(),
            $character->inventorySets->first(),
        );

        $character = $character->refresh();

        $this->assertEmpty($character->inventorySets->first()->slots);
        $this->assertCount(2, $character->inventory->slots);
    }
}
