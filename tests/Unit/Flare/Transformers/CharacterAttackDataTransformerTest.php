<?php

namespace Tests\Unit\Flare\Transformers;

use App\Game\Character\CharacterAttack\DataSets\CharacterAttackData;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackDataTransformer;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterAttackDataTransformerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_character_attack_data_class_is_autoloaded(): void
    {
        $attackData = resolve(CharacterAttackData::class);

        $this->assertInstanceOf(CharacterAttackData::class, $attackData);
    }

    public function test_character_attack_data_transformer_produces_attack_array(): void
    {
        $item = $this->createItem([
            'type' => ItemType::WEAPON->value,
            'base_damage' => 10,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'right-hand')
            ->getCharacter();

        $transformer = resolve(CharacterAttackDataTransformer::class);
        $result = $transformer->transform($character);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attack', $result);
    }
}
