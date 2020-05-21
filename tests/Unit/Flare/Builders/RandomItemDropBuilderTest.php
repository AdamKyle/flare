<?php

namespace Tests\Unit\Flare\Builders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Item;

class RandomItemDropBuilderTest extends TestCase
{

    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setup();

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
        ]);

        $item = $this->createItem([
            'name' => 'Bloody Spear',
            'type' => 'weapon',
        ]);

        $this->character = (new CharacterSetup)->setupCharacter([], $this->createUser())
                                               ->giveItem($item)
                                               ->equipLeftHand()
                                               ->setSkill('Looting', [
                                                   'looting_level' => 100,
                                                   'looting_bonus' => 100,
                                               ])
                                               ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCreatesRegularItem() {
        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes(config('game.item_affixes'))
                                    ->setArtifactProperties(config('game.artifact_properties'));

        $looting = $this->character->skills->where('name', 'Looting')->first();
        $looting->update([
            'skill_bonus' => -100
        ]);

        $item = $randomItemGenerator->generateItem($this->character);

        $this->assertNull($item->artifactProperty);
        $this->assertNull($item->itemAffix);
    }

    public function testCreateEnchantedItem() {
        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes(config('game.item_affixes'))
                                    ->setArtifactProperties(config('game.artifact_properties'));

        $looting = $this->character->skills->where('name', 'Looting')->first();
        $looting->update([
            'skill_bonus' => 100
        ]);

        $item = $randomItemGenerator->generateItem($this->character);

        $this->assertNotNull($item->artifactProperty);
        $this->assertTrue($item->itemAffixes->isNotEmpty());
    }

    public function testCreateEnchantedItemWithAffixes() {

        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes([
                                        [
                                           "name" => "Gathers Hunt",
                                           "base_damage_mod" => 2,
                                           "type" => "prefix",
                                           "description" => "Once, long ago, hunters would gather and collectivly they would bring back a feast for the ages.",
                                        ]
                                    ])
                                    ->setArtifactProperties(config('game.artifact_properties'));

        foreach(Item::all() as $item) {
            $item->itemAffixes()->create([
                'name' => 'Something',
                'base_damage_mod' => 6,
                'type' => 'suffix',
                'description' => 'test',
            ]);
        }

        $looting = $this->character->skills->where('name', 'Looting')->first();
        $looting->update([
            'skill_bonus' => 100
        ]);

        $item = $randomItemGenerator->generateItem($this->character);

        $this->assertTrue($item->itemAffixes->isNotEmpty());

        Item::first()->itemAffixes()->first()->delete();
    }

    public function testDontCreateEnchantedItemWithSameAffixes() {

        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes([
                                        [
                                            'name' => 'Something',
                                            'base_damage_mod' => 6,
                                            'type' => 'suffix',
                                            'description' => 'test',
                                        ]
                                    ])
                                    ->setArtifactProperties(config('game.artifact_properties'));

        foreach(Item::all() as $item) {
            $item->itemAffixes()->create([
                'name' => 'Something',
                'base_damage_mod' => 6,
                'type' => 'suffix',
                'description' => 'test',
            ]);
        }

        $looting = $this->character->skills->where('name', 'Looting')->first();
        $looting->update([
            'skill_bonus' => 100
        ]);

        $item = $randomItemGenerator->generateItem($this->character);

        $this->assertTrue($item->itemAffixes->isNotEmpty());
    }

    public function testCreateOneHundredItems() {
        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes(config('game.item_affixes'))
                                    ->setArtifactProperties(config('game.artifact_properties'));

        $looting = $this->character->skills->where('name', 'Looting')->first();

        $looting->update([
            'skill_bonus' => 1000
        ]);

        $this->character->refresh();

        for ($i = 0; $i < 100; $i++) {
            $item = $randomItemGenerator->generateItem($this->character);

            $this->assertNotNull($item->artifactProperty);
            $this->assertTrue($item->itemAffixes->isNotEmpty());
        }

        $this->assertEquals(6, Item::count());
    }
}
