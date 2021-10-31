<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateClass;

class CharacterAttackBuilderTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateClass;

    public function testBuildAttackDataVampire()
    {
        $character = $this->createCharacter('Vampire');

        $characterAttackBuilder = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        $this->assertNotEmpty($characterAttackBuilder->buildAttack(false));
        $this->assertNotEmpty($characterAttackBuilder->buildAttack(true));
    }

    public function testBuildAttackDataProphet()
    {
        $character = $this->createCharacter('Prophet');

        $characterAttackBuilder = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        $this->assertNotEmpty($characterAttackBuilder->buildAttack(false));
        $this->assertNotEmpty($characterAttackBuilder->buildAttack(true));
    }

    public function testBuildAttackDataHeretic()
    {
        $character = $this->createCharacter('Heretic');

        $characterAttackBuilder = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        $this->assertNotEmpty($characterAttackBuilder->buildAttack(false));
        $this->assertNotEmpty($characterAttackBuilder->buildAttack(true));
    }

    public function testBuildAttackDataFighter()
    {
        $character = $this->createCharacter('Fighter');

        $characterAttackBuilder = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        $this->assertNotEmpty($characterAttackBuilder->buildAttack(false));
        $this->assertNotEmpty($characterAttackBuilder->buildAttack(true));
    }

    public function testBuildAttackDataRanger()
    {
        $character = $this->createCharacter('Ranger');

        $characterAttackBuilder = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        $this->assertNotEmpty($characterAttackBuilder->buildAttack(false));
        $this->assertNotEmpty($characterAttackBuilder->buildAttack(true));
    }

    public function testBuildAttackDataThief()
    {
        $character = $this->createCharacter('Thief');

        $characterAttackBuilder = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        $this->assertNotEmpty($characterAttackBuilder->buildAttack(false));
        $this->assertNotEmpty($characterAttackBuilder->buildAttack(true));
    }

    protected function createCharacter(string $className): Character {
        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type'          => 'weapon',
                'item_suffix_id' => $this->createItemAffix([
                    'steal_life_amount' => 0.20
                ])
            ]), 0, 'left-hand', true)
            ->putItemInSet($this->createItem([
                'type'          => 'weapon',
                'item_suffix_id' => $this->createItemAffix([
                    'steal_life_amount' => 0.20
                ])
            ]), 0, 'right-hand', true)
            ->putItemInSet($this->createItem([
                'type'          => 'spell-damage',
                'item_prefix_id' => $this->createItemAffix([
                    'entranced_chance' => 0.20
                ])
            ]), 0, 'spell-one', true)
            ->putItemInSet($this->createItem([
                'type'          => 'spell-healing',
                'item_suffix_id' => $this->createItemAffix([
                    'damage' => 400
                ])
            ]), 0, 'spell-two', true)
            ->putItemInSet($this->createItem([
                'type'          => 'ring',
                'item_suffix_id' => $this->createItemAffix([
                    'irresistible_damage' => false,
                    'damage'              => 100,
                    'damage_can_stack'    => true,
                ])
            ]), 0, 'ring-one', true)
            ->putItemInSet($this->createItem([
                'type'          => 'ring',
                'item_suffix_id' => $this->createItemAffix([
                    'irresistible_damage' => true,
                    'damage_can_stack'    => true,
                    'damage'              => 100,
                    'class_bonus'         => 0.09
                ])
            ]), 0, 'ring-two', true)
            ->putItemInSet($this->createItem([
                'type'          => 'artifact',
                'item_suffix_id' => $this->createItemAffix([
                    'irresistible_damage' => true,
                    'damage_can_stack'    => true,
                    'damage'              => 100,
                    'class_bonus'         => 0.09,
                    'devouring_light'     => 0.90
                ])
            ]), 0, 'artifact-one', true)
            ->getCharacterFactory()
            ->getCharacter(false);

        $character->update([
            'game_class_id' => $this->createClass([
                'name' => $className
            ])->id,
        ]);

        return $character->refresh();
    }
}
