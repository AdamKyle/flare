<?php

namespace Tests\Unit\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\Item;

class RandomItemDropBuilderTest extends TestCase
{

    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setup();

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
        ]);

        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $this->character = resolve(CharacterBuilder::class)->setRace($race)
                                                     ->setClass($class)
                                                     ->createCharacter($this->createUser(), 'sample')
                                                     ->assignSkills()
                                                     ->character();
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

    public function testCreateOneHundredItemsThereShouldOnlyBeTwo() {
        $randomItemGenerator = resolve(RandomItemDropBuilder::class)
                                    ->setItemAffixes(config('game.item_affixes'))
                                    ->setArtifactProperties(config('game.artifact_properties'));

        $looting = $this->character->skills->where('name', 'Looting')->first();

        $looting->update([
            'skill_bonus' => 100
        ]);

        for ($i = 0; $i < 100; $i++) {
            $item = $randomItemGenerator->generateItem($this->character);

            $this->assertNotNull($item->artifactProperty);
            $this->assertTrue($item->itemAffixes->isNotEmpty());
        }

        $this->assertEquals(2, Item::count());
    }
}
