<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Game\Events\Values\EventType;
use App\Game\Raids\Values\RaidType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;

class AnnouncementsControllerTest extends TestCase
{
    use CreateAnnouncement, CreateEvent, CreateLocation, CreateMonster, CreateRaid, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        $this->character = null;

        parent::tearDown();
    }

    public function test_non_raid_announcement_does_not_include_raid_identity(): void
    {
        $event = $this->createEvent(['type' => EventType::WEEKLY_CELESTIALS]);
        $this->createAnnouncement(['event_id' => $event->id]);

        $response = $this->actingAs($this->character->getCharacter()->user)
            ->getJson('/api/announcements');

        $response->assertOk();
        $response->assertJsonMissingPath('0.event.raid_identity');
        $this->assertSame('Weekly Celestials', $response->json('0.event_name'));
    }

    public function test_raid_announcement_includes_minimal_raid_identity(): void
    {
        $monster = $this->createMonster();
        $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $location->id,
            'raid_type' => RaidType::ICE_QUEEN,
        ]);
        $event = $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
        ]);
        $this->createAnnouncement(['event_id' => $event->id]);

        $response = $this->actingAs($this->character->getCharacter()->user)
            ->getJson('/api/announcements');

        $response->assertOk();
        $response->assertJsonPath('0.event.raid_identity.id', $raid->id);
        $response->assertJsonPath('0.event.raid_identity.raid_type', RaidType::ICE_QUEEN);
        $response->assertJsonPath('0.event.raid_identity.name', 'Ice Queen Raid');
        $this->assertSame('Ice Queen Raid', $response->json('0.event_name'));
        $this->assertSame(
            ['id', 'raid_type', 'name'],
            array_keys($response->json('0.event.raid_identity'))
        );
        $response->assertJsonMissingPath('0.event.raid.story');
    }

    public function test_raid_announcement_without_a_raid_falls_back_to_the_event_name(): void
    {
        $event = $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'raid_id' => null,
        ]);
        $this->createAnnouncement(['event_id' => $event->id]);

        $response = $this->actingAs($this->character->getCharacter()->user)
            ->getJson('/api/announcements');

        $response->assertOk();
        $response->assertJsonMissingPath('0.event.raid_identity');
        $this->assertSame('Raid Event', $response->json('0.event_name'));
    }

    public function test_announcement_whose_event_was_deleted_still_reports_a_name(): void
    {
        $event = $this->createEvent(['type' => EventType::WEEKLY_CELESTIALS]);
        $this->createAnnouncement(['event_id' => $event->id]);

        $event->delete();

        $response = $this->actingAs($this->character->getCharacter()->user)
            ->getJson('/api/announcements');

        $response->assertOk();
        $this->assertNull($response->json('0.event'));
        $this->assertSame('Ended Event', $response->json('0.event_name'));
    }
}
