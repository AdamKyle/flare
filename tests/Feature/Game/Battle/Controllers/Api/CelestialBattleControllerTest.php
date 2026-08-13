<?php

namespace Tests\Feature\Game\Battle\Controllers\Api;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class CelestialBattleControllerTest extends TestCase
{
    use CreateCelestials, CreateGameMap, CreateMonster, CreateNpc, RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        $this->characterFactory = null;

        parent::tearDown();
    }

    public function test_celestial_list_endpoint_is_small(): void
    {
        $character = $this->characterFactory->getCharacter();

        $eligibleCelestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'name' => 'Eligible Celestial',
        ]);

        $nonCelestialMonster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
            'name' => 'Regular Monster',
        ]);

        $celestialWithType = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => 1,
            'name' => 'Typed Celestial',
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/celestial-beings/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();

        $ids = collect($data['celestial_monsters'])->pluck('id')->all();

        $this->assertContains($eligibleCelestial->id, $ids);
        $this->assertNotContains($nonCelestialMonster->id, $ids);
        $this->assertNotContains($celestialWithType->id, $ids);

        foreach ($data['celestial_monsters'] as $celestialRow) {
            $this->assertSame(['id', 'name'], array_keys($celestialRow));
        }
    }

    public function test_celestial_list_endpoint_does_not_cross_maps(): void
    {
        $character = $this->characterFactory->getCharacter();

        $sameMapCelestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
        ]);

        $otherMap = $this->createGameMap(['name' => 'Other Map', 'path' => 'path']);

        $otherMapCelestial = $this->createMonster([
            'game_map_id' => $otherMap->id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/celestial-beings/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $ids = collect($data['celestial_monsters'])->pluck('id')->all();

        $this->assertContains($sameMapCelestial->id, $ids);
        $this->assertNotContains($otherMapCelestial->id, $ids);
    }

    public function test_celestial_stats_endpoint_returns_selected_full_stats(): void
    {
        $character = $this->characterFactory->updateCharacter([
            'gold' => 50,
            'gold_dust' => 5,
        ])->getCharacter();

        $celestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'name' => 'Selected Celestial',
            'str' => 11,
            'dex' => 12,
            'int' => 13,
            'dur' => 14,
            'agi' => 15,
            'chr' => 16,
            'focus' => 17,
            'ac' => 18,
            'health_range' => '100-200',
            'attack_range' => '10-20',
            'gold_cost' => 50,
            'gold_dust_cost' => 5,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/celestial-beings/'.$character->id.'/'.$celestial->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($celestial->id, $data['monster']['id']);
        $this->assertSame('Selected Celestial', $data['monster']['name']);
        $this->assertSame(11, $data['monster']['str']);
        $this->assertSame(12, $data['monster']['dex']);
        $this->assertSame(13, $data['monster']['int']);
        $this->assertSame(14, $data['monster']['dur']);
        $this->assertSame(15, $data['monster']['agi']);
        $this->assertSame(16, $data['monster']['chr']);
        $this->assertSame(17, $data['monster']['focus']);
        $this->assertSame(18, $data['monster']['ac']);
        $this->assertSame('100-200', $data['monster']['health_range']);
        $this->assertSame('10-20', $data['monster']['attack_range']);
        $this->assertSame(50, $data['monster']['gold_cost']);
        $this->assertSame(5, $data['monster']['gold_dust_cost']);
        $this->assertTrue($data['can_afford']);
    }

    public function test_celestial_stats_endpoint_rejects_another_map(): void
    {
        $character = $this->characterFactory->getCharacter();

        $otherMap = $this->createGameMap(['name' => 'Other Map', 'path' => 'path']);

        $otherMapCelestial = $this->createMonster([
            'game_map_id' => $otherMap->id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/celestial-beings/'.$character->id.'/'.$otherMapCelestial->id);

        $response->assertStatus(422);
        $this->assertSame('Invalid celestial selection.', json_decode($response->getContent(), true)['message']);
    }

    public function test_conjure_post_rejects_another_map_celestial(): void
    {
        $character = $this->characterFactory->getCharacter();

        $this->createNpc([
            'type' => NpcType::SUMMONER->value,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $otherMap = $this->createGameMap(['name' => 'Other Map', 'path' => 'path']);

        $otherMapCelestial = $this->createMonster([
            'game_map_id' => $otherMap->id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
        ]);

        $startingGold = $character->gold;
        $startingGoldDust = $character->gold_dust;

        $response = $this->actingAs($character->user)
            ->postJson('/api/conjure/'.$character->id, [
                'monster_id' => $otherMapCelestial->id,
                'type' => 'public',
            ]);

        $response->assertStatus(422);
        $this->assertSame('Invalid celestial selection.', json_decode($response->getContent(), true)['message']);
        $this->assertSame(0, CelestialFight::count());
        $this->assertSame($startingGold, $character->refresh()->gold);
        $this->assertSame($startingGoldDust, $character->refresh()->gold_dust);
    }

    public function test_conjure_post_insufficient_funds_returns_422(): void
    {
        $character = $this->characterFactory->getCharacter();

        $this->createNpc([
            'type' => NpcType::SUMMONER->value,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $celestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'gold_cost' => 999999,
            'gold_dust_cost' => 999999,
        ]);

        $startingGold = $character->gold;
        $startingGoldDust = $character->gold_dust;

        $response = $this->actingAs($character->user)
            ->postJson('/api/conjure/'.$character->id, [
                'monster_id' => $celestial->id,
                'type' => 'public',
            ]);

        $response->assertStatus(422);
        $this->assertSame('You cannot afford to conjure this celestial.', json_decode($response->getContent(), true)['message']);
        $this->assertSame(0, CelestialFight::count());
        $this->assertSame($startingGold, $character->refresh()->gold);
        $this->assertSame($startingGoldDust, $character->refresh()->gold_dust);
    }

    public function test_conjure_post_successful_public_conjure(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5);
            })
        );

        $character = $this->characterFactory
            ->updateCharacter(['gold' => 1000, 'gold_dust' => 1000])
            ->getCharacter();

        $this->createNpc([
            'type' => NpcType::SUMMONER->value,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $celestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'gold_cost' => 50,
            'gold_dust_cost' => 5,
            'health_range' => '10-20',
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/conjure/'.$character->id, [
                'monster_id' => $celestial->id,
                'type' => 'public',
            ]);

        $response->assertSuccessful();

        $this->assertSame(1, CelestialFight::count());

        $fight = CelestialFight::first();

        $this->assertSame($celestial->id, $fight->monster_id);
        $this->assertSame($character->id, $fight->character_id);
        $this->assertSame(CelestialConjureType::PUBLIC, $fight->type);
        $this->assertSame(950, $character->refresh()->gold);
        $this->assertSame(995, $character->refresh()->gold_dust);
    }

    public function test_conjure_post_successful_private_conjure(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5);
            })
        );

        $character = $this->characterFactory
            ->updateCharacter(['gold' => 1000, 'gold_dust' => 1000])
            ->getCharacter();

        $this->createNpc([
            'type' => NpcType::SUMMONER->value,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $celestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'gold_cost' => 50,
            'gold_dust_cost' => 5,
            'health_range' => '10-20',
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/conjure/'.$character->id, [
                'monster_id' => $celestial->id,
                'type' => 'private',
            ]);

        $response->assertSuccessful();

        $this->assertSame(1, CelestialFight::count());

        $fight = CelestialFight::first();

        $this->assertSame(CelestialConjureType::PRIVATE, $fight->type);
        $this->assertSame(950, $character->refresh()->gold);
        $this->assertSame(995, $character->refresh()->gold_dust);
    }

    public function test_public_conjure_announces_global_message_with_coordinates(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5);
            })
        );

        $character = $this->characterFactory
            ->updateCharacter(['gold' => 1000, 'gold_dust' => 1000])
            ->getCharacter();

        $this->createNpc([
            'type' => NpcType::SUMMONER->value,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $celestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'gold_cost' => 50,
            'gold_dust_cost' => 5,
            'health_range' => '10-20',
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/conjure/'.$character->id, [
                'monster_id' => $celestial->id,
                'type' => 'public',
            ]);

        $response->assertSuccessful();

        $fight = CelestialFight::first();
        $plane = $character->map->gameMap->name;

        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($celestial, $plane, $fight) {
            return str_contains($event->message, $celestial->name)
                && str_contains($event->message, $plane)
                && str_contains($event->message, (string) $fight->x_position)
                && str_contains($event->message, (string) $fight->y_position);
        });
    }

    public function test_private_conjure_does_not_send_global_celestial_announcement(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5);
            })
        );

        $character = $this->characterFactory
            ->updateCharacter(['gold' => 1000, 'gold_dust' => 1000])
            ->getCharacter();

        $this->createNpc([
            'type' => NpcType::SUMMONER->value,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $celestial = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
            'gold_cost' => 50,
            'gold_dust_cost' => 5,
            'health_range' => '10-20',
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/conjure/'.$character->id, [
                'monster_id' => $celestial->id,
                'type' => 'private',
            ]);

        $response->assertSuccessful();

        $this->assertSame(1, CelestialFight::count());

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function test_private_owner_can_fetch_their_fight(): void
    {
        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $fight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/celestial-fight/'.$character->id.'/'.$fight->id);

        $response->assertOk();
        $this->assertSame($monster->name, json_decode($response->getContent(), true)['fight']['monster_name']);
    }

    public function test_another_character_cannot_fetch_a_private_fight(): void
    {
        $characterA = $this->characterFactory->getCharacter();
        $characterB = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $characterA->map->game_map_id,
        ]);

        $fight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $characterA->id,
            'x_position' => $characterA->map->character_position_x,
            'y_position' => $characterA->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        $response = $this->actingAs($characterB->user)
            ->getJson('/api/celestial-fight/'.$characterB->id.'/'.$fight->id);

        $response->assertStatus(404);
        $this->assertSame('Celestial fight not found.', json_decode($response->getContent(), true)['message']);
        $this->assertSame(0, CharacterInCelestialFight::where('character_id', $characterB->id)->count());
    }

    public function test_another_character_cannot_attack_a_private_fight(): void
    {
        $characterA = $this->characterFactory->getCharacter();
        $characterB = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $characterA->map->game_map_id,
        ]);

        $fight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $characterA->id,
            'x_position' => $characterA->map->character_position_x,
            'y_position' => $characterA->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        $response = $this->actingAs($characterB->user)
            ->postJson('/api/attack-celestial/'.$characterB->id.'/'.$fight->id, [
                'attack_type' => 'attack',
            ]);

        $response->assertStatus(404);
        $this->assertSame('Celestial fight not found.', json_decode($response->getContent(), true)['message']);
        $this->assertSame(0, CharacterInCelestialFight::where('character_id', $characterB->id)->count());
        $this->assertSame(100, $fight->fresh()->current_health);
    }

    public function test_public_fight_remains_accessible_to_another_character(): void
    {
        $characterA = $this->characterFactory->getCharacter();
        $characterB = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $characterA->map->game_map_id,
        ]);

        $fight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $characterA->id,
            'x_position' => $characterB->map->character_position_x,
            'y_position' => $characterB->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $response = $this->actingAs($characterB->user)
            ->getJson('/api/celestial-fight/'.$characterB->id.'/'.$fight->id);

        $response->assertOk();
        $this->assertSame($monster->name, json_decode($response->getContent(), true)['fight']['monster_name']);
    }
}
