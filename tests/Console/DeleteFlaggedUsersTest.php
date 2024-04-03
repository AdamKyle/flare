<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\GameMap;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Values\NpcTypes;


class DeleteFlaggedUsersTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateRole, CreateGameSkill, CreateNpc, CreateItem;

    public function setUp(): void {
        parent::setUp();

        $this->createAdmin($this->createAdminRole());

        $this->character = (new CharacterFactory())->createBaseCharacter()
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
            'type'        => NpcTypes::KINGDOM_HOLDER,
        ]);

        UserSiteAccessStatistics::create([
            'amount_signed_in'  => 1,
            'amount_registered' => 1,
        ]);

        Mail::fake();

        Queue::fake();
    }

    public function testDeleteInactiveFlaggedUsers() {
        $this->character->user()->update([
            'will_be_deleted' => true,
        ]);

        $this->assertEquals(0, $this->artisan('delete:flagged-users'));

        Queue::assertPushed(AccountDeletionJob::class);
    }

    public function testDoNotDeletedNonFlaggedUsers() {
        $this->assertEquals(0, $this->artisan('delete:flagged-users'));

        Queue::assertNotPushed(AccountDeletionJob::class);
    }
}
