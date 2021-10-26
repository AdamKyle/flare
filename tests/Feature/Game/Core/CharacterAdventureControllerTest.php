<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\AdventureLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateNotification;

class CharacterAdventureControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        CreateItem,
        CreateUser,
        CreateNotification;

    private $character;

    private $adventure;

    public function setUp(): void
    {
        parent::setUp();

        $this->adventure = $this->createNewAdventure();

        $item            = $this->createItem([
                               'name' => 'Spear',
                               'base_damage' => 6,
                               'type' => 'weapon',
                               'crafting_type' => 'weapon',
                           ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->updateSkill('Looting', [
                                                     'xp_towards'         => 0.10,
                                                     'level'              => 0,
                                                     'currently_training' => true,
                                                 ])
                                                 ->adventureManagement()
                                                 ->assignLog(
                                                     $this->adventure,
                                                     $item,
                                                     'Looting'
                                                 )
                                                 ->getCharacterFactory();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->adventure = null;
    }
}
