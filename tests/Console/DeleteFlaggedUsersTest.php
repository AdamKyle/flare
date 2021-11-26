<?php

namespace Tests\Console;


use App\Flare\Jobs\DailyGoldDustJob;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Values\NpcTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class DeleteFlaggedUsersTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateRole, CreateGameSkill, CreateAdventure, CreateNpc;

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

        Mail::fake();
    }

    public function testDeleteInactiveFlaggedUsers() {
        $this->character->user()->update([
            'will_be_deleted' => true,
        ]);

        $this->assertEquals(0, $this->artisan('delete:flagged-users'));

        $this->assertEquals(1, User::count());

        $this->assertEquals(0, Character::count());
    }

    public function testDoNotDeletedNonFlaggedUsers() {
        $this->assertEquals(0, $this->artisan('delete:flagged-users'));

        $this->assertEquals(2, User::count());

        $this->assertEquals(1, Character::count());
    }
}
