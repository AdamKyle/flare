<?php

namespace App\Flare\Models;

use App\Game\Automation\Values\AutomationType;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Values\CharacterClass;
use Database\Factories\CharacterFactory;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'damage_stat',
        'game_race_id',
        'game_class_id',
        'inventory_max',
        'alchemy_bag_limit',
        'gem_bag_limit',
        'can_attack',
        'can_move',
        'can_craft',
        'can_spin',
        'is_dead',
        'can_engage_celestials',
        'can_move_again_at',
        'can_attack_again_at',
        'can_craft_again_at',
        'can_settle_again_at',
        'can_spin_again_at',
        'can_engage_celestials_again_at',
        'force_name_change',
        'spell_evasion',
        'artifact_annulment',
        'is_attack_automation_locked',
        'is_mass_embezzling',
        'level',
        'xp',
        'xp_next',
        'xp_penalty',
        'str',
        'dur',
        'dex',
        'chr',
        'int',
        'agi',
        'focus',
        'ac',
        'gold',
        'gold_dust',
        'shards',
        'copper_coins',
        'reincarnated_stat_increase',
        'times_reincarnated',
        'base_stat_mod',
        'base_damage_stat_mod',
        'gem_world_introduction_acknowledged_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'inventory_max' => 'integer',
        'alchemy_bag_limit' => 'integer',
        'gem_bag_limit' => 'integer',
        'can_attack' => 'boolean',
        'can_move' => 'boolean',
        'can_craft' => 'boolean',
        'can_spin' => 'boolean',
        'is_dead' => 'boolean',
        'force_name_change' => 'boolean',
        'is_attack_automation_locked' => 'boolean',
        'can_engage_celestials' => 'boolean',
        'can_move_again_at' => 'datetime',
        'can_attack_again_at' => 'datetime',
        'can_craft_again_at' => 'datetime',
        'can_settle_again_at' => 'datetime',
        'can_spin_again_at' => 'datetime',
        'can_engage_celestials_again_at' => 'datetime',
        'level' => 'integer',
        'xp' => 'integer',
        'xp_next' => 'integer',
        'xp_penalty' => 'float',
        'str' => 'integer',
        'dur' => 'integer',
        'dex' => 'integer',
        'chr' => 'integer',
        'int' => 'integer',
        'agi' => 'integer',
        'focus' => 'integer',
        'ac' => 'integer',
        'gold' => 'integer',
        'gold_dust' => 'integer',
        'shards' => 'integer',
        'copper_coins' => 'integer',
        'reincarnated_stat_increase' => 'integer',
        'times_reincarnated' => 'integer',
        'base_stat_mod' => 'float',
        'base_damage_stat_mod' => 'float',
        'gem_world_introduction_acknowledged_at' => 'datetime',
    ];

    protected $appends = [
        'is_auto_battling',
    ];

    /**
     * Get the Game Race of this Character.
     *
     * @return BelongsTo
     */
    public function race()
    {
        return $this->belongsTo(GameRace::class, 'game_race_id', 'id');
    }

    /**
     * Get the Game Class of this Character.
     *
     * @return BelongsTo
     */
    public function class()
    {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    /**
     * Get the skills this Character has learned.
     *
     * @return HasMany
     */
    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * Get the User who owns this Character.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get this Character's main inventory.
     *
     * @return HasOne
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'character_id', 'id');
    }

    /**
     * Get this Character's inventory sets.
     *
     * @return HasMany
     */
    public function inventorySets()
    {
        return $this->hasMany(InventorySet::class, 'character_id', 'id');
    }

    /**
     * Get this Character's Gem Bag.
     *
     * @return HasOne
     */
    public function gemBag()
    {
        return $this->hasOne(GemBag::class, 'character_id', 'id');
    }

    /**
     * Get this Character's Alchemy Bag.
     *
     * @return HasOne
     */
    public function alchemyBag()
    {
        return $this->hasOne(AlchemyBag::class, 'character_id', 'id');
    }

    /**
     * Get this Character's personal Gem progression for every Map Gem profile.
     *
     * @return HasMany
     */
    public function gameMapGemProgressions(): HasMany
    {
        return $this->hasMany(CharacterGameMapGemProgression::class);
    }

    /**
     * Get this Character's personal Gem progression for every Location Gem profile.
     *
     * @return HasMany
     */
    public function gameLocationGemProgressions(): HasMany
    {
        return $this->hasMany(CharacterGameLocationGemProgression::class);
    }

    /**
     * Get this Character's active Gem Scrolls applied to Map Gem Worlds.
     *
     * @return HasMany
     */
    public function gameMapGemScrolls(): HasMany
    {
        return $this->hasMany(CharacterGameMapGemScroll::class);
    }

    /**
     * Get this Character's active Gem Scrolls applied to Location Gem Worlds.
     *
     * @return HasMany
     */
    public function gameLocationGemScrolls(): HasMany
    {
        return $this->hasMany(CharacterGameLocationGemScroll::class);
    }

    /**
     * Get this Character's faction progress for each map.
     *
     * @return HasMany
     */
    public function factions()
    {
        return $this->hasMany(Faction::class, 'character_id', 'id');
    }

    /**
     * Get this Character's faction loyalty records.
     *
     * @return HasMany
     */
    public function factionLoyalties()
    {
        return $this->hasMany(FactionLoyalty::class, 'character_id', 'id');
    }

    /**
     * Get this Character's current map position record.
     *
     * @return HasOne
     */
    public function map()
    {
        return $this->hasOne(Map::class);
    }

    /**
     * Return this Character's current X map position.
     *
     * @return int
     */
    public function getXPositionAttribute()
    {
        return $this->map->character_position_x;
    }

    /**
     * Return this Character's current Y map position.
     *
     * @return int
     */
    public function getYPositionAttribute()
    {
        return $this->map->character_position_y;
    }

    /**
     * Return the image path of the Game Map this Character is on.
     *
     * @return string
     */
    public function getMapUrlAttribute()
    {
        return $this->map->gameMap->path;
    }

    /**
     * Count the Kingdoms this Character owns.
     *
     * @return int
     */
    public function getKingdomsCountAttribute()
    {
        return $this->kingdoms->count();
    }

    /**
     * Get the Kingdoms this Character owns.
     *
     * @return HasMany
     */
    public function kingdoms()
    {
        return $this->hasMany(Kingdom::class, 'character_id', 'id');
    }

    /**
     * Get the Kingdom attack logs recorded for this Character.
     *
     * @return HasMany
     */
    public function kingdomAttackLogs()
    {
        return $this->hasMany(KingdomLog::class, 'character_id', 'id');
    }

    /**
     * Get the unit movement queues started by this Character.
     *
     * @return HasMany
     */
    public function unitMovementQueues()
    {
        return $this->hasMany(UnitMovementQueue::class, 'character_id', 'id');
    }

    /**
     * Get the boons applied to this Character.
     *
     * @return HasMany
     */
    public function boons()
    {
        return $this->hasMany(CharacterBoon::class, 'character_id', 'id');
    }

    /**
     * Get the quests and guide quests this Character has completed.
     *
     * @return HasMany
     */
    public function questsCompleted()
    {
        return $this->hasMany(QuestsCompleted::class, 'character_id', 'id');
    }

    /**
     * Get the automations currently recorded for this Character.
     *
     * @return HasMany
     */
    public function currentAutomations()
    {
        return $this->hasMany(CharacterAutomation::class, 'character_id', 'id');
    }

    /**
     * Get this Character's passive skills.
     *
     * @return HasMany
     */
    public function passiveSkills()
    {
        return $this->hasMany(CharacterPassiveSkill::class, 'character_id', 'id');
    }

    /**
     * Get this Character's class ranks.
     *
     * @return HasMany
     */
    public function classRanks()
    {
        return $this->hasMany(CharacterClassRank::class, 'character_id', 'id');
    }

    /**
     * Get the class specials this Character has equipped.
     *
     * @return HasMany
     */
    public function classSpecialsEquipped()
    {
        return $this->hasMany(CharacterClassSpecialtiesEquipped::class, 'character_id', 'id');
    }

    /**
     * Get this Character's global event participation records.
     *
     * @return HasMany
     */
    public function globalEventParticipation()
    {
        return $this->hasMany(GlobalEventParticipation::class, 'character_id', 'id');
    }

    /**
     * Get this Character's global event kill records.
     *
     * @return HasMany
     */
    public function globalEventKills()
    {
        return $this->hasMany(GlobalEventKill::class, 'character_id', 'id');
    }

    /**
     * Get this Character's global event crafting records.
     *
     * @return HasMany
     */
    public function globalEventCrafts()
    {
        return $this->hasMany(GlobalEventCraft::class, 'character_id', 'id');
    }

    /**
     * Get this Character's global event enchanting records.
     *
     * @return HasMany
     */
    public function globalEventEnchants()
    {
        return $this->hasMany(GlobalEventEnchant::class, 'character_id', 'id');
    }

    /**
     * Get this Character's weekly battle fight records.
     *
     * @return HasMany
     */
    public function weeklyBattleFights()
    {
        return $this->hasMany(WeeklyMonsterFight::class, 'character_id', 'id');
    }

    /**
     * Determine whether this Character currently has any automation running.
     *
     * @return bool
     */
    public function getIsAutoBattlingAttribute()
    {
        if ($this->relationLoaded('currentAutomations')) {
            return $this->currentAutomations->isNotEmpty();
        }

        return $this->currentAutomations()->exists();
    }

    /**
     * Determine whether this Character has a Faction Loyalty automation that has not completed.
     *
     * @return bool
     */
    public function isFactionLoyaltyAutomationRunning(): bool
    {
        return $this->currentAutomations()
            ->where('type', AutomationType::FACTION_LOYALTY->value)
            ->where('completed_at', '>', now())
            ->exists();
    }

    /**
     * Return a stat builder prepared for this Character's calculated information.
     *
     * @return CharacterStatBuilder
     */
    public function getInformation(): CharacterStatBuilder
    {
        $info = resolve(CharacterStatBuilder::class);

        return $info->setCharacter($this);
    }

    /**
     * Return the class value object for this Character's Game Class.
     *
     * @return CharacterClass
     *
     * @throws Exception
     */
    public function classType(): CharacterClass
    {
        return CharacterClass::from($this->class->name);
    }

    /**
     * Determine whether this Character's User has an active session.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return Session::where('user_id', $this->user_id)->exists();
    }

    /**
     * Count the unequipped main inventory slots, excluding quest, alchemy and gem Items.
     *
     * @return int
     */
    public function getInventoryCount(): int
    {
        $inventoryId = Inventory::where('character_id', $this->id)->value('id');

        if (is_null($inventoryId)) {
            return 0;
        }

        return InventorySlot::where('inventory_slots.inventory_id', $inventoryId)
            ->where('inventory_slots.equipped', false)
            ->join('items', function ($join) {
                $join->on('items.id', '=', 'inventory_slots.item_id')
                    ->where('items.type', '!=', 'quest')
                    ->where('items.type', '!=', 'alchemy')
                    ->where('items.type', '!=', 'gem');
            })
            ->count();
    }

    /**
     * Sum the Gem amounts held in this Character's Gem Bag.
     *
     * @return int
     */
    public function getGemBagCount(): int
    {
        $gemBag = $this->gemBag;

        if (is_null($gemBag)) {
            return 0;
        }

        return intval(GemBagSlot::where('gem_bag_id', $gemBag->id)->sum('amount'));
    }

    /**
     * Sum the Alchemy Bag slot amounts for this Character, excluding Compensation Caches.
     *
     * @return int
     */
    public function getAlchemyBagCount(): int
    {
        $alchemyBag = $this->alchemyBag;

        if (is_null($alchemyBag)) {
            return 0;
        }

        return intval(
            AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
                ->whereDoesntHave('item', function ($query) {
                    $query->whereNotNull('currency_cache_type');
                })
                ->sum('amount')
        );
    }

    /**
     * Determine whether the main inventory has reached its limit.
     *
     * @return bool
     */
    public function isInventoryFull(): bool
    {
        return $this->getInventoryCount() >= $this->inventory_max;
    }

    /**
     * Determine whether the Gem Bag has reached its limit.
     *
     * @return bool
     */
    public function isGemBagFull(): bool
    {
        return $this->getGemBagCount() >= $this->gem_bag_limit;
    }

    /**
     * Determine whether the Alchemy Bag has reached its limit.
     *
     * @return bool
     */
    public function isAlchemyBagFull(): bool
    {
        return $this->getAlchemyBagCount() >= $this->alchemy_bag_limit;
    }

    /**
     * Determine whether the given amount fits in the Alchemy Bag without exceeding its limit.
     *
     * @param int $amount
     * @return bool
     */
    public function canAddToAlchemyBag(int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->getAlchemyBagCount() + $amount <= $this->alchemy_bag_limit;
    }

    /**
     * Determine whether the given amount fits in the Gem Bag without exceeding its limit.
     *
     * @param int $amount
     * @return bool
     */
    public function canAddToGemBag(int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->getGemBagCount() + $amount <= $this->gem_bag_limit;
    }

    /**
     * Return the main inventory count used for capacity checks.
     *
     * @return int
     */
    public function totalInventoryCount(): int
    {
        return $this->getInventoryCount();
    }

    /**
     * Create the model factory used by Laravel for this Character.
     *
     * @return CharacterFactory
     */
    protected static function newFactory()
    {
        return CharacterFactory::new();
    }
}
