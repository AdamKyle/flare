<?php

namespace Tests\Unit\Flare\Builders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Values\ItemUsabilityType;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItemAffix;

class CharacterInformationBuilderTest extends TestCase {

    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateCharacterBoon;

    private $character;

    private $characterInfo;

    public function setUp(): void {
        parent::setup();

        $this->character     = (new CharacterFactory)->createBaseCharacter();
        $this->characterInfo = resolve(CharacterInformationBuilder::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character     = null;
        $this->characterInfo = null;
    }

    public function testCanSetCharacter() {
        $info = $this->characterInfo->setCharacter($this->character->getCharacter());

        $this->assertEquals($this->character->getCharacter()->name, $info->getCharacter()->name);
    }

    public function testCharacterGetsBaseStatBackForModdedStat() {
        $stat = $this->characterInfo->setCharacter($this->character->getCharacter())->statMod('str');

        $this->assertEquals($this->character->getCharacter()->str, $stat);
    }

    public function testCharacterGetsModdedStatBackForModdedStat() {
        $character = $this->character->inventoryManagement()
                                     ->giveItem($this->createItem([
                                         'name' => 'sample',
                                         'item_suffix_id' => $this->createItemAffix([
                                             'type' => 'suffix',
                                             'str_mod' => 2.0
                                         ])->id,
                                         'type' => 'weapon'
                                     ]))
                                     ->equipItem('left_hand', 'sample')
                                     ->getCharacter();

        $stat = $this->characterInfo->setCharacter($character)->statMod('str');

        $this->assertTrue($stat > $character->str);
    }

    public function testCharacterGetsModdedStatsFromBoons() {
        $character = $this->character->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 0.08,
            'started'      => now(),
            'complete'     => now()->addMinutes(100),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $stat = $this->characterInfo->setCharacter($character)->statMod('str');

        $this->assertTrue($stat > $character->str);
    }

    public function testGetBestClassBonus() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'class_bonus' => 2.0
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'type' => 'prefix',
                    'class_bonus' => 12.0
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $classBonus = $this->characterInfo->setCharacter($character)->classBonus();

        $this->assertEquals(12.0, $classBonus);
    }

    public function testGetFirstStatReducingPrefix() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'reduces_enemy_stats' => true,
                    'str_reduction' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'prefix',
                    'reduces_enemy_stats' => true,
                    'str_reduction' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $itemAffix = $this->characterInfo->setCharacter($character)->findPrefixStatReductionAffix();

        $this->assertEquals('sample 2', $itemAffix->name);
    }

    public function testSumAllLifeStealingAffixes() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'steal_life_amount' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'prefix',
                    'steal_life_amount' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $amount = $this->characterInfo->setCharacter($character)->findLifeStealingAffixes(true);

        $this->assertEquals(4.0, $amount);
    }

    public function testTakeBestLifeStealingAffixes() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'steal_life_amount' => 6.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'prefix',
                    'steal_life_amount' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $amount = $this->characterInfo->setCharacter($character)->findLifeStealingAffixes(false);

        $this->assertEquals(6.0, $amount);
    }

    public function testTakeBestEntrancedChanceAffixes() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'entranced_chance' => 6.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'prefix',
                    'entranced_chance' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $amount = $this->characterInfo->setCharacter($character)->getEntrancedChance();

        $this->assertEquals(6.0, $amount);
    }

    public function testGetCollectionOfStatReductionAffixes() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'reduces_enemy_stats' => true,
                    'dex_reduction' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'suffix',
                    'reduces_enemy_stats' => true,
                    'dex_reduction' => 2.0,
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $collection = $this->characterInfo->setCharacter($character)->findSuffixStatReductionAffixes();

        $this->assertNotEmpty($collection);
    }

    public function testGetAffixDamageThatCannotStack() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'damage_can_stack' => false,
                    'damage' => 100
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'prefix',
                    'damage_can_stack' => false,
                    'damage' => 1000
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $amount = $this->characterInfo->setCharacter($character)->getTotalAffixDamage(false);

        $this->assertEquals(1000, $amount);
    }

    public function testGetAffixDamageThatCanStack() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'name' => 'sample',
                'item_suffix_id' => $this->createItemAffix([
                    'name' => 'sample',
                    'type' => 'suffix',
                    'damage_can_stack' => true,
                    'damage' => 1000
                ])->id,
                'type' => 'weapon'
            ]))
            ->giveItem($this->createItem([
                'name' => 'sample 2',
                'item_prefix_id' => $this->createItemAffix([
                    'name' => 'sample 2',
                    'type' => 'prefix',
                    'damage_can_stack' => true,
                    'damage' => 100
                ])->id,
                'type' => 'weapon'
            ]))
            ->equipItem('left_hand', 'sample')
            ->equipItem('right_hand', 'sample 2')
            ->getCharacter();

        $amount = $this->characterInfo->setCharacter($character)->getTotalAffixDamage(true);

        $this->assertEquals(1100, $amount);
    }
}
