<?php

namespace Tests\Unit\Flare\Builders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use Tests\Traits\CreateItemAffix;

class RandomItemDropBuilderTest extends TestCase {

    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setup();

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
        ]);

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'suffix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'prefix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->updateSkill('Looting', [
                                                     'level' => 100,
                                                 ])
                                                 ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCreatesRegularItem() {
        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes(ItemAffix::all());

        $looting = $this->character->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', 'Looting');
        })->first();

        $looting->update([
            'skill_bonus_per_level' => -100
        ]);

        $item = $randomItemGenerator->generateItem($this->character);

        $this->assertNotEmpty($item->getRelations());
    }

    public function testCreateEnchantedItem() {
        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes(ItemAffix::all());

        $looting = $this->character->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', 'Looting');
        })->first();

        $looting->update([
            'skill_bonus' => 100
        ]);

        $item = $randomItemGenerator->generateItem($this->character);

        $this->assertNotEmpty($item->getRelations());
    }

    public function testFailToCreateEnchantedItemWhenItemAlreadyHasSuffixAndPrefixOfTheSameType() {
        Item::first()->delete();

        $this->createItem([
            'name' => 'something',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 5
        ]);

        Item::first()->update([
            'item_suffix_id' => ItemAffix::where('type', 'suffix')->first()->id,
            'item_prefix_id' => ItemAffix::where('type', 'prefix')->first()->id,
        ]);

        $randomItemBuilder = $this->getMockBuilder(RandomItemDropBuilder::class)
             ->setMethods(array('hasSameAffix'))
             ->getMock();

        $randomItemBuilder->expects($this->any())
             ->method('hasSameAffix')
             ->willReturn(true);
             
        
        $randomItemBuilder->setItemAffixes(ItemAffix::all());

        $randomItemBuilder->generateItem($this->character);

        $this->assertEquals(Item::count(), 1);
    }

    public function testCreateEnchantedItemWhenItemAlreadyHasSuffixAndPrefixOfTheSameType() {
        Item::first()->delete();

        $this->createItem([
            'name' => 'something',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 5
        ]);

        Item::first()->update([
            'item_suffix_id' => ItemAffix::where('type', 'suffix')->first()->id,
            'item_prefix_id' => ItemAffix::where('type', 'prefix')->first()->id,
        ]);

        $randomItemBuilder = $this->getMockBuilder(RandomItemDropBuilder::class)
             ->setMethods(array('hasSameAffix'))
             ->getMock();

        $randomItemBuilder->expects($this->any())
             ->method('hasSameAffix')
             ->willReturn(false);
        
        $randomItemBuilder->setItemAffixes(ItemAffix::all());

        $randomItemBuilder->generateItem($this->character);

        $this->assertEquals(Item::count(), 2);
    }

    public function testCreateItemWithAffix() {
        Item::first()->delete();

        ItemAffix::truncate();

        $affix = $this->createItemAffix([
            'name' => 'Godly'
        ]);

        $this->createItem([
            'name' => 'something',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 5,
        ]);

        $randomItemBuilder = $this->getMockBuilder(RandomItemDropBuilder::class)
        ->setMethods(array('fetchRandomItemAffix', 'shouldHaveItemAffix'))
        ->getMock();

        $randomItemBuilder->expects($this->any())
            ->method('fetchRandomItemAffix')
            ->willReturn($affix);

        $randomItemBuilder->expects($this->any())
            ->method('shouldHaveItemAffix')
            ->willReturn(true);
        
        $randomItemBuilder->setItemAffixes(ItemAffix::all());

        $randomItemBuilder->generateItem($this->character);

        $this->assertEquals(Item::count(), 2);
    }

    public function testFailToCreateItemWithAffix() {
        Item::first()->delete();

        ItemAffix::truncate();

        $affix = $this->createItemAffix();

        $this->createItem([
            'name' => 'something',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 5,
            'item_suffix_id' => $affix->id,
        ]);

        $randomItemBuilder = $this->getMockBuilder(RandomItemDropBuilder::class)
        ->setMethods(array('fetchRandomItemAffix', 'shouldHaveItemAffix'))
        ->getMock();

        $randomItemBuilder->expects($this->any())
            ->method('fetchRandomItemAffix')
            ->willReturn($affix);

        $randomItemBuilder->expects($this->any())
            ->method('shouldHaveItemAffix')
            ->willReturn(true);
        
        $randomItemBuilder->setItemAffixes(ItemAffix::all());

        $randomItemBuilder->generateItem($this->character);

        $this->assertEquals(Item::count(), 1);
    }
}
