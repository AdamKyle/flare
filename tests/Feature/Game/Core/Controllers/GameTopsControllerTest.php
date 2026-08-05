<?php

namespace Tests\Feature\Game\Core\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GameTopsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tops_requires_authentication(): void
    {
        $response = $this->call('GET', '/game/tops');

        $response->assertStatus(302);
    }

    public function test_tops_lists_characters_ordered_by_level_descending(): void
    {
        $viewer = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $strongCharacter = (new CharacterFactory)->createBaseCharacter()->updateCharacter(['name' => 'Ironclad', 'level' => 50])->getCharacter();
        $weakCharacter = (new CharacterFactory)->createBaseCharacter()->updateCharacter(['name' => 'Weakling', 'level' => 2])->getCharacter();

        $response = $this->actingAs($viewer->user)->call('GET', '/game/tops');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertGreaterThan(0, strpos($content, 'Ironclad'));
        $this->assertLessThan(strpos($content, 'Weakling'), strpos($content, 'Ironclad'));
    }

    public function test_tops_search_filters_by_character_name(): void
    {
        $viewer = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        (new CharacterFactory)->createBaseCharacter()->updateCharacter(['name' => 'Ironclad'])->getCharacter();
        (new CharacterFactory)->createBaseCharacter()->updateCharacter(['name' => 'Weakling'])->getCharacter();

        $response = $this->actingAs($viewer->user)->call('GET', '/game/tops', ['search' => 'Ironclad']);

        $response->assertOk();
        $response->assertSee('Ironclad');
        $response->assertDontSee('Weakling');
    }

    public function test_character_profile_shows_equipped_items(): void
    {
        $viewer = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->equipStartingEquipment();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($viewer->user)->get('/game/tops/characters/'.$character->id);

        $response->assertSee('Rusty Dagger');
        $response->assertSee('Right hand');
    }
}
