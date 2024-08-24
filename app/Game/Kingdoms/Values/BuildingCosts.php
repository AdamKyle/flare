<?php

namespace App\Game\Kingdoms\Values;

class BuildingCosts
{
    const KEEP = 'Keep';

    const FARM = 'Farm';

    const LUMBER_MILL = 'Lumber Mill';

    const STONE_QUARRY = 'Stone Quarry';

    const CLAY_PIT = 'Clay Pit';

    const IRON_MINE = 'Iron Mine';

    const WALLS = 'Walls';

    const BARRACKS = 'Barracks';

    const CHURCH = 'Church';

    const SETTLERS_HALL = 'Settler\'s Hall';

    const GOBLIN_COIN_BANK = 'Goblin Coin Bank';

    const CANNONEER_SHOP = 'Cannoneer Shop';

    const CALVARY = 'Calvary Training Grounds';

    const BLACKSMITHS_FURNACE = 'Blacksmith\'s Furnace';

    const AIRSHIP_FIELDS = 'Airship Fields';

    const MARKET_PLACE = 'Market Place';

    public static $values = [
        self::KEEP => self::KEEP,
        self::FARM => self::FARM,
        self::LUMBER_MILL => self::LUMBER_MILL,
        self::STONE_QUARRY => self::STONE_QUARRY,
        self::CLAY_PIT => self::CLAY_PIT,
        self::IRON_MINE => self::IRON_MINE,
        self::WALLS => self::WALLS,
        self::BARRACKS => self::BARRACKS,
        self::CHURCH => self::CHURCH,
        self::SETTLERS_HALL => self::SETTLERS_HALL,
        self::GOBLIN_COIN_BANK => self::GOBLIN_COIN_BANK,
        self::CANNONEER_SHOP => self::CANNONEER_SHOP,
        self::CALVARY => self::CALVARY,
        self::BLACKSMITHS_FURNACE => self::BLACKSMITHS_FURNACE,
        self::AIRSHIP_FIELDS => self::AIRSHIP_FIELDS,
        self::MARKET_PLACE => self::MARKET_PLACE,
    ];

    private $name;

    public function __construct(string $name)
    {
        if (! in_array($name, self::$values)) {
            throw new \Exception($name.' does not exist.');
        }

        $this->name = $name;
    }

    public function fetchCost(): int
    {

        switch ($this->name) {
            case self::KEEP:
                return 10000;
            case self::FARM:
                return 500;
            case self::LUMBER_MILL:
                return 250;
            case self::STONE_QUARRY:
                return 750;
            case self::CLAY_PIT:
                return 150;
            case self::IRON_MINE:
                return 1000;
            case self::WALLS:
                return 5000;
            case self::BARRACKS:
                return 2500;
            case self::CHURCH:
                return 3500;
            case self::SETTLERS_HALL:
                return 50000;
            case self::GOBLIN_COIN_BANK:
                return 100000;
            case self::CANNONEER_SHOP:
                return 150000;
            case self::CALVARY:
                return 15000;
            case self::BLACKSMITHS_FURNACE:
                return 500000;
            case self::AIRSHIP_FIELDS:
                return 75000;
            default:
                return 0;
        }
    }
}
