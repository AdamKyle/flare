<?php

namespace App\Flare\Values;

class ItemEffectsValue
{
    /**
     * @var string
     */
    private $value;

    const WALK_ON_WATER = 'walk-on-water';

    const WALK_ON_DEATH_WATER = 'walk-on-death-water';

    const LABYRINTH = 'labyrinth';

    const DUNGEON = 'dungeon';

    const SHADOW_PLANE = 'shadow-plane';

    const HELL = 'hell';

    const PURGATORY = 'purgatory';

    const TELEPORT_TO_CELESTIAL = 'teleport-to-celestial';

    const AFFIXES_IRRESISTIBLE = 'affixes-irresistible';

    const CONTINUE_LEVELING = 'continue-leveling';

    const GOLD_DUST_RUSH = 'gold-dust-rush';

    const MASS_EMBEZZLE = 'mass-embezzle';

    const WALK_ON_MAGMA = 'walk-on-magma';

    const QUEEN_OF_HEARTS = 'speak-to-queen-of-hearts';

    const FACTION_POINTS = 'effects-faction-points';

    const GET_COPPER_COINS = 'get-copper-coins';

    const ENTER_PURGATORY_HOUSE = 'enter-purgatory-house';

    const HIDE_CHAT_LOCATION = 'hide-chat-location';

    const WALK_ON_ICE = 'walk-on-ice';

    const SETTLE_IN_ICE_PLANE = 'settle-on-the-ice-plane';

    const THE_OLD_CHURCH = 'the-old-church';

    const MERCENARY_SLOT_BONUS = 'mercenary-slot-bonus';

    const WALK_ON_DELUSIONAL_MEMORIES_WATER = 'walk-on-delusional-memories-water';

    const TWISTED_TREE_BRANCH = 'access-twisted-memories';

    const TWISTED_DUNGEONS = 'twisted-dungeons';

    /**
     * @var string[]
     */
    protected static $values = [
        self::WALK_ON_WATER => 'walk-on-water',
        self::WALK_ON_DEATH_WATER => 'walk-on-death-water',
        self::WALK_ON_MAGMA => 'walk-on-magma',
        self::LABYRINTH => 'labyrinth',
        self::DUNGEON => 'dungeon',
        self::SHADOW_PLANE => 'shadow-plane',
        self::HELL => 'hell',
        self::TELEPORT_TO_CELESTIAL => 'teleport-to-celestial',
        self::AFFIXES_IRRESISTIBLE => 'affixes-irresistible',
        self::CONTINUE_LEVELING => 'continue-leveling',
        self::GOLD_DUST_RUSH => 'gold-dust-rush',
        self::MASS_EMBEZZLE => 'mass-embezzle',
        self::QUEEN_OF_HEARTS => 'speak-to-queen-of-hearts',
        self::PURGATORY => 'purgatory',
        self::FACTION_POINTS => 'effects-faction-points',
        self::GET_COPPER_COINS => 'get-copper-coins',
        self::ENTER_PURGATORY_HOUSE => 'enter-purgatory-house',
        self::HIDE_CHAT_LOCATION => 'hide-chat-location',
        self::WALK_ON_ICE => 'walk-on-ice',
        self::SETTLE_IN_ICE_PLANE => 'settle-on-the-ice-plane',
        self::THE_OLD_CHURCH => 'the-old-church',
        self::MERCENARY_SLOT_BONUS => 'mercenary-slot-bonus',
        self::WALK_ON_DELUSIONAL_MEMORIES_WATER => 'walk-on-delusional-memories-water',
        self::TWISTED_TREE_BRANCH => 'access-twisted-memories',
        self::TWISTED_DUNGEONS => 'twisted-dungeons',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @throws \Exception
     */
    public function __construct(string $value)
    {

        if (! in_array($value, self::$values)) {
            throw new \Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * is walk on water?
     */
    public function walkOnWater(): bool
    {
        return $this->value === self::WALK_ON_WATER;
    }

    /**
     * is walk on ice?
     */
    public function walkOnIce(): bool
    {
        return $this->value === self::WALK_ON_ICE;
    }

    /**
     * Is Walk on death water?
     */
    public function walkOnDeathWater(): bool
    {
        return $this->value === self::WALK_ON_DEATH_WATER;
    }

    /**
     * is Walk on Magma?
     */
    public function walkOnMagma(): bool
    {
        return $this->value === self::WALK_ON_MAGMA;
    }

    /**
     * Can Access Labyrinth
     */
    public function labyrinth(): bool
    {
        return $this->value === self::LABYRINTH;
    }

    /**
     * Can access Dungeon
     */
    public function dungeon(): bool
    {
        return $this->value === self::DUNGEON;
    }

    /**
     * Can access Shadow plane
     */
    public function shadowPlane(): bool
    {
        return $this->value === self::SHADOW_PLANE;
    }

    /**
     * Can Access Hell
     */
    public function hell(): bool
    {
        return $this->value === self::HELL;
    }

    /**
     * Is purgatory?
     */
    public function purgatory(): bool
    {
        return $this->value === self::PURGATORY;
    }

    /**
     * Does this item make your affixes irresistible to the enemy?
     */
    public function areAffixesIrresistible(): bool
    {
        return $this->value === self::AFFIXES_IRRESISTIBLE;
    }

    /**
     * does this item allow for a gold dust rush?
     */
    public function isGoldDustRush(): bool
    {
        return $this->value === self::GOLD_DUST_RUSH;
    }

    /**
     * Can we teleport to celestial?
     */
    public function teleportToCelestial(): bool
    {
        return $this->value === self::TELEPORT_TO_CELESTIAL;
    }

    /**
     * Can mass embezzle?
     */
    public function canMassEmbezzle(): bool
    {
        return $this->value === self::MASS_EMBEZZLE;
    }

    /**
     * Can speak to the queen of hearts?
     */
    public function canSpeakToQueenOfHearts(): bool
    {
        return $this->value === self::QUEEN_OF_HEARTS;
    }

    /**
     * Does this effect Faction points?
     */
    public function effectsFactionPoints(): bool
    {
        return $this->value === self::FACTION_POINTS;
    }

    /**
     * Does this let the player receive copper coins?
     */
    public function getCopperCoins(): bool
    {
        return $this->value === self::GET_COPPER_COINS;
    }

    /**
     * Does this let the player enter into the purgatory smith house?
     */
    public function canEnterPurgatorySmithHouse(): bool
    {
        return $this->value === self::ENTER_PURGATORY_HOUSE;
    }

    /**
     * Does this item hide chat location?
     */
    public function hideChatLocation(): bool
    {
        return $this->value === self::HIDE_CHAT_LOCATION;
    }

    /**
     * Does this item allows you to settle kingdoms on the ice plane?
     */
    public function canSettleOnIcePlane(): bool
    {
        return $this->value === self::SETTLE_IN_ICE_PLANE;
    }

    /**
     * Does this item allow players to gain rewards at The Old Church on the ice plane?
     */
    public function canGainRewardsAtTheOldChurch(): bool
    {
        return $this->value === self::THE_OLD_CHURCH;
    }

    /**
     * Is mercenary slot bonus.
     */
    public function mercenarySlotBonus(): bool
    {
        return $this->value === self::MERCENARY_SLOT_BONUS;
    }

    /**
     * Is an item that allows us to walk on the delusional memories water?
     */
    public function walkOnDelusionalMemoriesWater(): bool
    {
        return $this->value === self::WALK_ON_DELUSIONAL_MEMORIES_WATER;
    }

    /**
     * Is an item that allows us to access the twisted memories?
     */
    public function accessTwistedMemories(): bool
    {
        return $this->value === self::TWISTED_TREE_BRANCH;
    }

    /**
     * Is an item that allows one to access the twisted dungeons?
     *
     * @return boolean
     */
    public function accessTwistedDungeons(): bool {
        return $this->value === self::TWISTED_DUNGEONS;
    }
}
