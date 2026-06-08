<?php

namespace Tests\Feature\Http\Controllers;

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

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_can_delete_character()
    {
        $user = $this->character->user;

        $this->actingAs($user)->post(route('delete.account', [
            'user' => $user->id,
        ]));

        Queue::assertPushed(AccountDeletionJob::class);
    }

    public function test_guest_cannot_delete_account(): void
    {
        $user = $this->character->user;

        $response = $this->call('POST', route('delete.account', [
            'user' => $user->id,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
        Queue::assertNotPushed(AccountDeletionJob::class);
    }

    public function test_ajax_guest_cannot_delete_account(): void
    {
        $user = $this->character->user;

        $response = $this->call(
            'POST',
            route('delete.account', ['user' => $user->id]),
            [],
            [],
            [],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response->assertUnauthorized();
        Queue::assertNotPushed(AccountDeletionJob::class);
    }

    public function test_cannot_delete_character()
    {
        $user = $this->character->user;

        $anotherUser = $this->createUser();

        $response = $this->actingAs($anotherUser)->post(route('delete.account', [
            'user' => $user->id,
        ]))->response;

        $response->assertSessionHas('error', 'You cannot do that.');
    }

    public function test_unauthenticated_cannot_reset_account(): void
    {
        $user = $this->character->user;

        $response = $this->call('POST', route('reset.account', ['user' => $user->id]));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    public function test_authenticated_user_cannot_reset_another_users_account(): void
    {
        $user = $this->character->user;
        $anotherUser = $this->createUser();

        $response = $this->actingAs($anotherUser)->post(route('reset.account', [
            'user' => $user->id,
        ]))->response;

        $response->assertSessionHas('error', 'You cannot do that.');
    }

    public function test_authenticated_user_can_reset_own_account(): void
    {
        $user = $this->character->user;
        $characterId = $this->character->id;
        $this->createItem([
            'type' => 'sword',
            'skill_level_required' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('reset.account', [
            'user' => $user->id,
        ]))->response;

        $response->assertRedirect(route('game'));
        $response->assertSessionHas('success', 'Character has been re-rolled!');
        $this->assertNotSame($characterId, $user->refresh()->character->id);
    }
}
