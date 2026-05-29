<?php

namespace Tests\Feature\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameClassSpecial;

class ClassRanksControllerTest extends TestCase
{
    use CreateGameClassSpecial, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_character_class_ranks()
    {

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/' . $character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_ranks']);
    }

    public function testExplorationBlocksClassRankList(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/class-ranks/' . $character->id);

        $response->assertStatus(422);
    }

    public function testDelveBlocksClassRankList(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/class-ranks/' . $character->id);

        $response->assertStatus(422);
    }

    public function testFactionLoyaltyBlocksClassRankList(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/class-ranks/' . $character->id);

        $response->assertStatus(422);
    }

    public function testGetCharacterClassSpecials()
    {

        $character = $this->character->getCharacter();

        $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/' . $character->id . '/specials');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_specialties']);
        $this->assertCount(0, $jsonData['specials_equipped']);
        $this->assertCount(1, $jsonData['class_ranks']);
        $this->assertCount(0, $jsonData['other_class_specials']);
    }

    public function test_equip_special()
    {

        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/' . $character->id . '/' . $classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Equipped class special: ' . $classSpecial->name, $jsonData['message']);
    }

    public function test_unequip_special()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/' . $character->id . '/' . $specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped class special: ' . $classSpecial->name, $jsonData['message']);
    }
}
