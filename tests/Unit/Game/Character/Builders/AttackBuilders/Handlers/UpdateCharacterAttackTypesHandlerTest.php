<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Handlers;

use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use Cache;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class UpdateCharacterAttackTypesHandlerTest extends TestCase
{
    use CreateClass, CreateGameClassSpecial, CreateGameMap, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->updateCharacterAttackTypesHandler = resolve(UpdateCharacterAttackTypesHandler::class);
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

    public function test_update_character_attack_cache()
    {
        Event::fake();

        $character = $this->setUpCharacterForTests();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
    }

    public function test_update_character_attack_cache_is_created()
    {

        $character = $this->setUpCharacterForTests();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        $this->assertNotNull(
            Cache::get('character-attack-data-'.$character->id)
        );
    }
}
