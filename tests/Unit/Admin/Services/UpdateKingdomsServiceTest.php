<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\UpdateKingdomsService;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameMap;
use App\Flare\Mail\GenericMail;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUser;
use Tests\TestCase;

class UpdateKingdomsServiceTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateKingdom, CreateGameBuilding, CreateGameUnit;

    public function testAddKingdomBuildingToKingdomWithService()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $building = $this->createGameBuilding();

        $units     = $this->createGameUnits([], 6);

        resolve(UpdateKingdomsService::class)->updateKingdomKingdomBuildings($building, $units->pluck('id')->toArray(), 3);

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->isNotEmpty());
    }


}
