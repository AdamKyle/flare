<?php

namespace Tests\Feature;

use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\GameMap;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Values\NpcTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AccountDeletionControllerTest extends TestCase
{
    use CreateGameSkill,
        CreateItem,
        CreateNpc,
        CreateRole,
        CreateUser,
        RefreshDatabase;

    private $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->createAdmin($this->createAdminRole());

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->createMarketListing()
            ->assignSkill($this->createGameSkill())
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
            'game_map_id' => GameMap::first()->id,
            'type' => NpcTypes::KINGDOM_HOLDER,
        ]);

        UserSiteAccessStatistics::create([
            'amount_signed_in' => 1,
            'amount_registered' => 1,
        ]);

        Queue::fake();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanDeleteCharacter()
    {
        $user = $this->character->user;

        $this->actingAs($user)->post(route('delete.account', [
            'user' => $user->id,
        ]));

        Queue::assertPushed(AccountDeletionJob::class);
    }

    public function testCannotDeleteCharacter()
    {
        $user = $this->character->user;

        $anotherUser = $this->createUser();

        $response = $this->actingAs($anotherUser)->post(route('delete.account', [
            'user' => $user->id,
        ]))->response;

        $response->assertSessionHas('error', 'You cannot do that.');
    }
}
