<?php

namespace Tests\Setup\Character;

use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Tests\Traits\CreateCapitalCityQueue;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateKingdomBuilding;

class KingdomManagement
{
    use CreateCapitalCityQueue,
        CreateGameBuilding,
        CreateGameUnit,
        CreateKingdom,
        CreateKingdomBuilding,
        KingdomCache;

    private Character $character;

    private CharacterFactory $characterFactory;

    private Kingdom $kingdom;

    private ?CapitalCityBuildingQueue $capitalCityBuildingQueue = null;

    private ?CapitalCityBuildingCancellation $capitalCityBuildingCancellation = null;

    private ?CapitalCityUnitQueue $capitalCityUnitQueue = null;

    private ?CapitalCityUnitCancellation $capitalCityUnitCancellation = null;

    private ?CapitalCityResourceRequest $capitalCityResourceRequest = null;

    public function __construct(Character $character, CharacterFactory $characterFactory)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Creates a kingdom.
     *
     * This kingdom is assigned to the kingdom and the same map
     * the character is on.
     *
     * @var array | []
     */
    public function assignKingdom(array $options = []): KingdomManagement
    {
        $this->kingdom = $this->createKingdom(array_merge([
            'character_id' => $this->character->id,
            'game_map_id' => $this->character->map->game_map_id,
            'treasury' => 0,
            'last_walked' => now(),
        ], $options));

        $this->addKingdomToCache($this->character, $this->kingdom);

        return $this;
    }

    /**
     * Assigns a building to the kingdom.
     *
     * If the kingdom does not exist, we will throw an error.
     *
     * Options may be passed to the game building that represent the game_buildings attributes.
     *
     * The kingdom building will be assigned to the kingdom itself, additional options may be
     * passed in. These options match the kingdom_buildings attributes.
     *
     * @param  array  $gameBuildingOptions  | []
     * @param array kingdomBuildingOptions | []
     *
     * @throws Exception
     */
    public function assignBuilding(array $gameBuildingOptions = [], array $kingdomBuildingOptions = []): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $gameBuilding = $this->createGameBuilding($gameBuildingOptions);

        $this->createKingdomBuilding(array_merge([
            'game_building_id' => $gameBuilding->id,
            'kingdom_id' => $this->kingdom->id,
        ], $kingdomBuildingOptions));

        return $this;
    }

    public function assignCapitalCityBuildingQueue(array $queueOptions = [], array $requestOptions = []): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $building = $this->kingdom->buildings()->first();

        $this->capitalCityBuildingQueue = $this->createCapitalCityBuildingQueue(array_merge([
            'character_id' => $this->character->id,
            'kingdom_id' => $this->kingdom->id,
            'requested_kingdom' => $this->kingdom->id,
            'building_request_data' => [array_merge([
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ], $requestOptions)],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
        ], $queueOptions));

        return $this;
    }

    public function getCapitalCityBuildingQueue(): ?CapitalCityBuildingQueue
    {
        return $this->capitalCityBuildingQueue?->refresh();
    }

    public function assignCapitalCityBuildingCancellation(array $options = []): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $building = $this->kingdom->buildings()->first();

        $this->capitalCityBuildingCancellation = $this->createCapitalCityBuildingCancellation(array_merge([
            'building_id' => $building->id,
            'kingdom_id' => $this->kingdom->id,
            'request_kingdom_id' => $this->kingdom->id,
            'character_id' => $this->character->id,
            'capital_city_building_queue_id' => $this->capitalCityBuildingQueue?->id,
        ], $options));

        return $this;
    }

    public function getCapitalCityBuildingCancellation(): ?CapitalCityBuildingCancellation
    {
        return $this->capitalCityBuildingCancellation?->refresh();
    }

    public function assignCapitalCityUnitQueue(array $queueOptions = [], array $requestOptions = []): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $kingdomUnit = $this->kingdom->units()->first();

        if (is_null($kingdomUnit)) {
            $gameUnit = $this->createGameUnit();

            $kingdomUnit = $this->createKingdomUnit([
                'game_unit_id' => $gameUnit->id,
                'kingdom_id' => $this->kingdom->id,
                'amount' => 500,
            ]);
        }

        $this->capitalCityUnitQueue = $this->createCapitalCityUnitQueue(array_merge([
            'character_id' => $this->character->id,
            'kingdom_id' => $this->kingdom->id,
            'requested_kingdom' => $this->kingdom->id,
            'unit_request_data' => [array_merge([
                'name' => $kingdomUnit->gameUnit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ], $requestOptions)],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
        ], $queueOptions));

        return $this;
    }

    public function getCapitalCityUnitQueue(): ?CapitalCityUnitQueue
    {
        return $this->capitalCityUnitQueue?->refresh();
    }

    public function assignCapitalCityUnitCancellation(array $options = []): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $kingdomUnit = $this->kingdom->units()->first();

        if (is_null($kingdomUnit)) {
            $gameUnit = $this->createGameUnit();

            $kingdomUnit = $this->createKingdomUnit([
                'game_unit_id' => $gameUnit->id,
                'kingdom_id' => $this->kingdom->id,
                'amount' => 500,
            ]);
        }

        $this->capitalCityUnitCancellation = $this->createCapitalCityUnitCancellation(array_merge([
            'unit_id' => $kingdomUnit->game_unit_id,
            'kingdom_id' => $this->kingdom->id,
            'request_kingdom_id' => $this->kingdom->id,
            'character_id' => $this->character->id,
            'capital_city_unit_queue_id' => $this->capitalCityUnitQueue?->id,
        ], $options));

        return $this;
    }

    public function getCapitalCityUnitCancellation(): ?CapitalCityUnitCancellation
    {
        return $this->capitalCityUnitCancellation?->refresh();
    }

    public function assignCapitalCityResourceRequest(array $options = []): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $this->capitalCityResourceRequest = $this->createCapitalCityResourceRequest(array_merge([
            'kingdom_requesting_id' => $this->kingdom->id,
            'request_from_kingdom_id' => $this->kingdom->id,
            'resources' => [
                'wood' => 0,
                'clay' => 0,
                'stone' => 0,
                'iron' => 0,
            ],
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
        ], $options));

        return $this;
    }

    public function getCapitalCityResourceRequest(): ?CapitalCityResourceRequest
    {
        return $this->capitalCityResourceRequest?->refresh();
    }

    /**
     * Assigns units to a kingdom.
     *
     * Creates a game unit with supplied gameUnitOptions that maps to the game_units attributes.
     *
     * Assigns that game unit to the kingdom. If there is no kingdom, there will be an exception thrown.
     *
     * The amount of units, by default, is 500.
     *
     * @param  array  $gameUnitOptions  | []
     *
     * @throws Exception
     */
    public function assignUnits(array $gameUnitOptions = [], int $amount = 500): KingdomManagement
    {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $gameUnit = $this->createGameUnit($gameUnitOptions);

        $this->createKingdomUnit([
            'game_unit_id' => $gameUnit->id,
            'kingdom_id' => $this->kingdom->id,
            'amount' => $amount,
        ]);

        return $this;
    }

    /**
     * Returns the refreshed kingdom.
     */
    public function getKingdom(): Kingdom
    {
        return $this->kingdom->refresh();
    }

    /**
     * Returns the refreshed character.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Returns the refreshed user.
     */
    public function getUser(): User
    {
        return $this->character->refresh()->user;
    }

    /**
     * Returns the character Factory
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }
}
