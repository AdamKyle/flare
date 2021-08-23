<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateKingdomBuilding;

class KingdomManagement {

    use CreateKingdom,
        CreateGameBuilding,
        CreateKingdomBuilding,
        CreateGameUnit,
        KingdomCache;

    /**
     * @var Character $charactr
     */
    private Character $charactr;

    /**
     * @var CharacterFactory $characterFactory
     */
    private CharacterFactory $characterFactory;

    /**
     * @var Kingdom $kingdom
     */
    private Kingdom $kingdom;

    public function __construct(Character $character, CharacterFactory $characterFactory)
    {
        $this->character        = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Creates a kingdom.
     *
     * This kingdom is assigned to the kingdom and the same map
     * the character is on.
     *
     * @var array $options | []
     * @return KingdomManagement
     */
    public function assignKingdom(array $options = []): KingdomManagement {
        $this->kingdom = $this->createKingdom(array_merge([
            'character_id' => $this->character->id,
            'game_map_id'  => $this->character->map->game_map_id,
        ], $options));

        $this->addKingdomToCache($this->character, $this->kingdom);

        return $this;
    }

    /**
     * Assigns a building to the kingdom.
     *
     * If the kingdom does not exist, we will throw an error.
     *
     * Options may be passed to the game building that repersent the game_buildings attributes.
     *
     * The kingdom building will be assigned to the kingdom it's self, additional options may be
     * passed in. These options match the kingdom_buildings attributes.
     *
     * @param array $gameBuildingOptions | []
     * @param array kingdomBuildingOptions | []
     * @throws Exception
     * @return KingdomManagement
     */
    public function assignBuilding(array $gameBuildingOptions = [], array $kingdomBuildingOptions = []): KingdomManagement {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $gameBuilding = $this->createGameBuilding($gameBuildingOptions);

        $this->createKingdomBuilding(array_merge([
            'game_building_id' => $gameBuilding->id,
            'kingdom_id'       => $this->kingdom->id,
        ], $kingdomBuildingOptions));

        return $this;
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
     * @param array $gameUnitOptions | []
     * @param int $amount
     * @throws Exception
     * @return KingdomManagement
     */
    public function assignUnits(array $gameUnitOptions = [], int $amount = 500): KingdomManagement {
        if (is_null($this->kingdom)) {
            throw new \Exception('You must create a kingdom first. Call createKingdom.');
        }

        $gameUnit = $this->createGameUnit($gameUnitOptions);

        $this->createKingdomUnit([
            'game_unit_id' => $gameUnit->id,
            'kingdom_id'   => $this->kingdom->id,
            'amount'       => $amount,
        ]);

        return $this;
    }

    /**
     * Returns the refreshed kingdom.
     *
     * @return Kingdom
     */
    public function getKingdom(): Kingdom {
        return $this->kingdom->refresh();
    }

    /**
     * Returns the refreshed character.
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    /**
     * Returns the refreshed user.
     *
     * @return User
     */
    public function getUser(): User {
        return $this->character->refresh()->user;
    }

    /**
     * Returns the character Factory
     *
     * @return CharacterFactory
     */
    public function getCharacterFactory(): CharacterFactory {
        return $this->characterFactory;
    }
}
