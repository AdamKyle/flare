<?php

namespace Tests\Feature\Game\ClassRanks\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameSkill;

class ClassRanksControllerTest extends TestCase
{
    use CreateGameClassSpecial, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_character_class_ranks_endpoint_returns_player_rank_contract(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/class-ranks/'.$character->id);

        $response->assertOk();
        $this->assertNotEmpty($response->json('class_ranks'));
        $this->assertArrayHasKey('class_detail', $response->json('class_ranks.0'));
    }

    public function test_character_class_specialties_endpoint_returns_player_specialty_contract(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/class-ranks/'.$character->id.'/specials');

        $response->assertOk();

        $specialty = collect($response->json('class_specialties'))->firstWhere('id', $classSpecial->id);
        $equippedRow = collect($response->json('specials_equipped'))->firstWhere('game_class_special_id', $classSpecial->id);

        $this->assertNotNull($specialty);
        $this->assertArrayHasKey('class_mastery', $specialty);
        $this->assertArrayHasKey('is_mastered', $equippedRow);
        $this->assertFalse($equippedRow['is_mastered']);
    }

    public function test_character_can_swap_an_equipped_specialty_for_an_accessible_specialty(): void
    {
        $character = $this->character->getCharacter();

        $currentlyEquipped = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $target = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $equipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $currentlyEquipped->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/swap-specialty/'.$character->id.'/'.$target->id.'/'.$equipped->id);

        $response->assertOk();

        $specialsEquipped = collect($response->json('specials_equipped'));

        $this->assertTrue($specialsEquipped->contains('game_class_special_id', $target->id));
        $this->assertFalse($specialsEquipped->contains('game_class_special_id', $currentlyEquipped->id));
    }
}
