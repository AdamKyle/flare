<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use Cache;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterAttackTypesCacheBuilderTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->updateCharacterAttackTypesHandler = null;
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

    public function test_update_character_attack_types_handler_and_exploration_log_update_event_is_called()
    {
        Event::fake();

        $character = $this->setUpCharacterForTests();

        CharacterAttackTypesCacheBuilder::dispatch($character, true);

        Event::assertDispatched(ExplorationLogUpdate::class);
    }

    public function test_update_character_attack_types_cache()
    {
        $character = $this->setUpCharacterForTests();

        CharacterAttackTypesCacheBuilder::dispatch($character, true);

        $this->assertNotNull(
            Cache::get('character-attack-data-'.$character->id)
        );
    }
}
