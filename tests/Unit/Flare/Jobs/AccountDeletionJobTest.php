<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameUnit;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Monster;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Services\CharacterDeletion;
use App\Flare\Values\NpcTypes;
use App\Game\Messages\Models\Message;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateSuggestionAndBugs;
use Tests\Traits\CreateUser;

class AccountDeletionJobTest extends TestCase
{
    use CreateGameSkill,
        CreateGlobalEventGoal,
        CreateItem,
        CreateMessage,
        CreateMonster,
        CreateNpc,
        CreateQuest,
        CreateRole,
        CreateSuggestionAndBugs,
        CreateUser,
        RefreshDatabase;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->createAdmin($this->createAdminRole());

        $this->characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->assignSkill($this->createGameSkill())
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacterFactory();

        $this->createNpc([
            'game_map_id' => GameMap::first()->id,
            'type' => NpcTypes::KINGDOM_HOLDER,
        ]);

        UserSiteAccessStatistics::create([
            'amount_signed_in' => 1,
            'amount_registered' => 1,
        ]);

        Mail::fake();
        Event::fake();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
    }

    public function test_user_is_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $userId = $character->user->id;

        AccountDeletionJob::dispatch($character->user);

        $this->assertNull(User::find($userId));
    }

    public function test_character_is_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $characterId = $character->id;

        AccountDeletionJob::dispatch($character->user);

        $this->assertNull(Character::find($characterId));
    }

    public function test_messages_are_preserved(): void
    {
        $character = $this->characterFactory->getCharacter();
        $user = $character->user;

        $message = $this->createMessage($user);

        AccountDeletionJob::dispatch($user);

        $this->assertNotNull(Message::find($message->id));
    }

    public function test_suggestions_are_preserved(): void
    {
        $character = $this->characterFactory->getCharacter();
        $user = $character->user;

        $suggestion = $this->createSuggestionAndBug([
            'character_id' => $character->id,
        ]);

        AccountDeletionJob::dispatch($user);

        $this->assertNotNull(\App\Flare\Models\SuggestionAndBugs::find($suggestion->id));
    }

    public function test_kingdoms_are_transferred_not_deleted(): void
    {
        $character = $this->characterFactory->getCharacter();
        $kingdomId = $character->kingdoms->first()->id;

        AccountDeletionJob::dispatch($character->user);

        $kingdom = Kingdom::find($kingdomId);
        $this->assertNotNull($kingdom);
        $this->assertTrue($kingdom->npc_owned);
    }

    public function test_kingdom_buildings_and_units_are_preserved(): void
    {
        $character = $this->characterFactory->getCharacter();
        $kingdom = $character->kingdoms->first();
        $buildingCount = $kingdom->buildings->count();
        $unitCount = $kingdom->units->count();

        AccountDeletionJob::dispatch($character->user);

        $kingdom = Kingdom::find($kingdom->id);
        $this->assertEquals($buildingCount, $kingdom->buildings->count());
        $this->assertEquals($unitCount, $kingdom->units->count());
    }

    public function test_email_is_sent_after_deletion(): void
    {
        $character = $this->characterFactory->getCharacter();
        $userEmail = $character->user->email;

        AccountDeletionJob::dispatch($character->user, true);

        Mail::assertSent(GenericMail::class, function (GenericMail $mail) use ($userEmail) {
            return $mail->hasTo($userEmail);
        });
    }

    public function test_email_is_not_sent_when_email_user_is_false(): void
    {
        $character = $this->characterFactory->getCharacter();

        AccountDeletionJob::dispatch($character->user, false);

        Mail::assertNotSent(GenericMail::class);
    }

    public function test_deletion_exception_is_not_swallowed(): void
    {
        $this->expectException(Exception::class);

        $character = $this->characterFactory->getCharacter();

        $this->instance(CharacterDeletion::class, Mockery::mock(CharacterDeletion::class, function ($mock) {
            $mock->shouldReceive('deleteCharacterFromUser')
                ->once()
                ->andThrow(new Exception('Deletion failed'));
        }));

        AccountDeletionJob::dispatch($character->user);
    }
}
