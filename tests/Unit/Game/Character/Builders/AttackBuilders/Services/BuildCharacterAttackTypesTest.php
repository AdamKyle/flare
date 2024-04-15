<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Services;

use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class BuildCharacterAttackTypesTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?BuildCharacterAttackTypes $buildCharacterAttackTypes;

    public function setUp(): void {
        $this->useMockForAttackDataCache = false;

        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->buildCharacterAttackTypes = resolve(BuildCharacterAttackTypes::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->buildCharacterAttackTypes = null;
    }

    private function setUpCharacterForTests(): Character {
        $item = $this->createItem([
            'type'        => WeaponTypes::STAVE,
            'base_damage' => 10,
        ]);

        $spellDamage = $this->createItem([
            'type'        => SpellTypes::DAMAGE,
            'base_damage' => 10,
        ]);

        return $this->character->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->giveItem($spellDamage, true, 'spell-one')
            ->getCharacter();
    }

    public function testBuildCharacterAttackTypesData() {
        $character = $this->setUpCharacterForTests();

        Cache::delete('character-attack-data-' . $character->id);

        $this->buildCharacterAttackTypes->buildCache($character);

        $this->assertNotNull(
            Cache::get('character-attack-data-' . $character->id)
        );
    }
}
