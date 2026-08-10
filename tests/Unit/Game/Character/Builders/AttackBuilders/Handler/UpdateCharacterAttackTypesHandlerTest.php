<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\Handler;

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
use Tests\Traits\CreateItemAffix;

class UpdateCharacterAttackTypesHandlerTest extends TestCase
{
    use CreateClass, CreateGameClassSpecial, CreateGameMap, CreateGameSkill, CreateItemAffix, RefreshDatabase;

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

    public function test_update_character_attack_cache()
    {
        Event::fake();

        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
    }

    public function test_update_character_attack_cache_is_created()
    {

        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        $this->assertNotNull(
            Cache::get('character-attack-data-'.$character->id)
        );
    }
}
