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

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
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

    public function test_character_attack_types_cache_builder_with_deductions_and_event_is_dispatched()
    {
        Event::fake();

        $character = $this->setUpCharacterForTests();

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
    }

    public function test_character_attack_types_cache_builder_with_deductions()
    {
        $character = $this->setUpCharacterForTests();

        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character);

        $this->assertNotNull(
            Cache::get('character-attack-data-'.$character->id)
        );
    }
}
