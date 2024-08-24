<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilderWithDeductions;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use Cache;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterAttackTypesCacheBuilderWithDeductionsTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    private function setUpCharacterForTests(): Character
    {
        $item = $this->createItem([
            'type' => WeaponTypes::STAVE,
            'base_damage' => 10,
        ]);

        $spellDamage = $this->createItem([
            'type' => SpellTypes::DAMAGE,
            'base_damage' => 10,
        ]);

        return $this->character->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->giveItem($spellDamage, true, 'spell-one')
            ->getCharacter();
    }

    public function testCharacterAttackTypesCacheBuilderWithDeductionsAndEventIsDispatched()
    {
        Event::fake();

        $character = $this->setUpCharacterForTests();

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
    }

    public function testCharacterAttackTypesCacheBuilderWithDeductions()
    {
        $character = $this->setUpCharacterForTests();

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character);

        $this->assertNotNull(
            Cache::get('character-attack-data-'.$character->id)
        );
    }
}
