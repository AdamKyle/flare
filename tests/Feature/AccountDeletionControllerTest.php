<?php

namespace Tests\Feature;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase, CreateGameSkill, CreateAdventure, CreateNpc, CreateUser, CreateRole;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->createAdmin($this->createAdminRole());

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                ->givePlayerLocation()
                                ->equipStartingEquipment()
                                ->assignSkill($this->createGameSkill())
                                ->createAdventureLog($this->createNewAdventure())
                                ->InventorySetManagement()
                                ->createInventorySets(10)
                                ->getCharacterFactory()
                                ->kingdomManagement()
                                ->assignKingdom()
                                ->assignBuilding()
                                ->assignUnits()
                                ->getCharacter();

        $this->createNpc([
            'name' => 'Sample',
            'real_name' => 'Sample',
            'game_map_id' => GameMap::first()->id,
            'type' => NpcTypes::KINGDOM_HOLDER,
        ]);

        UserSiteAccessStatistics::create([
            'amount_signed_in' => 1,
            'amount_registered' => 1,
        ]);

        Mail::fake();
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

        Mail::assertSent(GenericMail::class);

        $this->assertEmpty(Character::all());
        $this->assertEmpty(Inventory::all());
        $this->assertEmpty(InventorySet::all());
        $this->assertEmpty(Skill::whereNull('monster_id')->get());
        $this->assertEmpty(Kingdom::whereNotNull('character_id')->get());
        $this->assertCount(1, User::all());
    }

    public function testCannotDeleteCharacter() {
        $user = $this->character->user;

        $anotherUser = $this->createUser();

        $response = $this->actingAs($anotherUser)->post(route('delete.account', [
            'user' => $user->id,
        ]))->response;

        $response->assertSessionHas('error', 'You cannot do that.');

        $this->assertNotEmpty(Character::all());
        $this->assertNotEmpty(Inventory::all());
        $this->assertNotEmpty(InventorySet::all());
        $this->assertNotEmpty(Skill::whereNull('monster_id')->get());
        $this->assertNotEmpty(Kingdom::whereNotNull('character_id')->get());
        $this->assertCount(3, User::all());
    }
}
