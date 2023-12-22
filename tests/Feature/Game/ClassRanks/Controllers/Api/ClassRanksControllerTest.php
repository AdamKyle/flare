<?php

namespace Tests\Feature\Game\GuideQuest\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameClassSpecial;

class ClassRanksControllerTest extends TestCase {

    use RefreshDatabase, CreateGameClassSpecial;

    private ?CharacterFactory $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterClassRanks() {

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/' . $character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_ranks']);
    }

    public function testGetCharacterClassSpecials() {

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

    public function testEquipSpecial() {

        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/' . $classSpecial->id, [
                '_token' => csrf_token()
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Equipped class special: ' . $classSpecial->name, $jsonData['message']);
    }

    public function testUnequipSpecial() {
        $character    = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id'                            => $character->game_class_id,
            'specialty_damage'                         => 50000,
            'increase_specialty_damage_per_level'      => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id'            => $character->id,
            'game_class_special_id'   => $classSpecial->id,
            'level'                   => 0,
            'current_xp'              => 0,
            'required_xp'             => 100,
            'equipped'                => true,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/' . $specialtyEquipped->id, [
                '_token' => csrf_token()
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped class special: ' . $classSpecial->name, $jsonData['message']);
    }
}
