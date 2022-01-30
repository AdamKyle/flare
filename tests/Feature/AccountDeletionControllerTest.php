<?php

namespace Tests\Feature;

use App\Flare\Jobs\AccountDeletionJob;
use Illuminate\Support\Facades\Queue;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Models\GameMap;
use App\Flare\Values\NpcTypes;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AccountDeletionControllerTest extends TestCase {

    use RefreshDatabase,
        CreateGameSkill,
        CreateAdventure,
        CreateNpc,
        CreateUser,
        CreateRole;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->createAdmin($this->createAdminRole());

        $this->character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->createMarketListing()
            ->assignSkill($this->createGameSkill())
            ->createAdventureLog($this->createNewAdventure())
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $this->createNpc([
            'name'        => 'Sample',
            'real_name'   => 'Sample',
            'game_map_id' => GameMap::first()->id,
            'type'        => NpcTypes::KINGDOM_HOLDER,
        ]);

        UserSiteAccessStatistics::create([
            'amount_signed_in'  => 1,
            'amount_registered' => 1,
        ]);

        Queue::fake();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanDeleteCharacter() {
        $user = $this->character->user;

        $this->actingAs($user)->post(route('delete.account', [
            'user' => $user->id,
        ]));

        Queue::assertPushed(AccountDeletionJob::class);
    }

    public function testCannotDeleteCharacter() {
        $user = $this->character->user;

        $anotherUser = $this->createUser();

        $response = $this->actingAs($anotherUser)->post(route('delete.account', [
            'user' => $user->id,
        ]))->response;

        $response->assertSessionHas('error', 'You cannot do that.');
    }
}