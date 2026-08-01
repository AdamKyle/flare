<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Tops\Services\KingdomTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUser;

class KingdomTopsServiceTest extends TestCase
{
    use CreateCharacter, CreateGameMap, CreateKingdom, CreateUser, RefreshDatabase;

    public function test_kingdom_tops_excludes_npc_owned_kingdoms(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $map = $this->createGameMap();
        $this->createKingdom(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => false, 'treasury' => 100]);
        $this->createKingdom(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => true, 'treasury' => 500]);

        $data = $this->app->make(KingdomTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(1, $data['rows'][0]['kingdom_count']);
    }

    public function test_kingdom_tops_defaults_to_ranking_by_kingdom_count(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $map = $this->createGameMap();
        $this->createKingdom(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => false, 'treasury' => 100]);

        $data = $this->app->make(KingdomTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame('kingdom_count', $data['metric']);
    }
}
