<?php

namespace App\Flare\Models;

use App\Flare\Values\CharacterClassValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Database\Factories\CharacterFactory;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'inventory_max' => 'integer',
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
    ];

    protected $appends = [
        'is_auto_battling',
    ];

    public function race()
    {
        return $this->belongsTo(GameRace::class, 'game_race_id', 'id');
    }

    public function class()
    {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'character_id', 'id');
    }

    public function inventorySets()
    {
        return $this->hasMany(InventorySet::class, 'character_id', 'id');
    }

    public function gemBag()
    {
        return $this->hasOne(GemBag::class, 'character_id', 'id');
    }

    public function factions()
    {
        return $this->hasMany(Faction::class, 'character_id', 'id');
    }

    public function factionLoyalties()
    {
        return $this->hasMany(FactionLoyalty::class, 'character_id', 'id');
    }

    public function map()
    {
        return $this->hasOne(Map::class);
    }

    public function getXPositionAttribute()
    {
        return $this->map->character_position_x;
    }

    public function getYPositionAttribute()
    {
        return $this->map->character_position_y;
    }

    public function getMapUrlAttribute()
    {
        return $this->map->gameMap->path;
    }

    public function getKingdomsCountAttribute()
    {
        return $this->kingdoms->count();
    }

    public function kingdoms()
    {
        return $this->hasMany(Kingdom::class, 'character_id', 'id');
    }

    public function kingdomAttackLogs()
    {
        return $this->hasMany(KingdomLog::class, 'character_id', 'id');
    }

    public function unitMovementQueues()
    {
        return $this->hasMany(UnitMovementQueue::class, 'character_id', 'id');
    }

    public function boons()
    {
        return $this->hasMany(CharacterBoon::class, 'character_id', 'id');
    }

    public function questsCompleted()
    {
        return $this->hasMany(QuestsCompleted::class, 'character_id', 'id');
    }

    public function currentAutomations()
    {
        return $this->hasMany(CharacterAutomation::class, 'character_id', 'id');
    }

    public function passiveSkills()
    {
        return $this->hasMany(CharacterPassiveSkill::class, 'character_id', 'id');
    }

    public function classRanks()
    {
        return $this->hasMany(CharacterClassRank::class, 'character_id', 'id');
    }

    public function classSpecialsEquipped()
    {
        return $this->hasMany(CharacterClassSpecialtiesEquipped::class, 'character_id', 'id');
    }

    public function globalEventParticipation()
    {
        return $this->hasOne(GlobalEventParticipation::class, 'character_id', 'id');
    }

    public function globalEventKills()
    {
        return $this->hasOne(GlobalEventKill::class, 'character_id', 'id');
    }

    public function globalEventCrafts()
    {
        return $this->hasOne(GlobalEventCraft::class, 'character_id', 'id');
    }

    public function globalEventEnchants()
    {
        return $this->hasOne(GlobalEventEnchant::class, 'character_id', 'id');
    }

    public function weeklyBattleFights()
    {
        return $this->hasMany(WeeklyMonsterFight::class, 'character_id', 'id');
    }

    public function getIsAutoBattlingAttribute()
    {
        if ($this->relationLoaded('currentAutomations')) {
            return $this->currentAutomations->isNotEmpty();
        }

        return $this->currentAutomations()->exists();
    }

    /**
     * Allows one to get specific information from a character.
     *
     * By returning the CharacterStatBuilder class, we can allow you to get
     * multiple calculated sets of data.
     */
    public function getInformation(): CharacterStatBuilder
    {
        $info = resolve(CharacterStatBuilder::class);

        return $info->setCharacter($this);
    }

    /**
     * Returns the character class value.
     *
     * @throws Exception
     */
    public function classType(): CharacterClassValue
    {
        return new CharacterClassValue($this->class->name);
    }

    /**
     * Is the character logged in?
     */
    public function isLoggedIn(): bool
    {
        return Session::where('user_id', $this->user_id)->exists();
    }

    /**
     * Gets the inventory count.
     *
     * Excludes quest and equipped items.
     */
    public function getInventoryCount(): int
    {
        $inventoryId = Inventory::where('character_id', $this->id)->value('id');

        if (is_null($inventoryId)) {
            return 0;
        }

        $slotCount = InventorySlot::where('inventory_slots.inventory_id', $inventoryId)
            ->where('inventory_slots.equipped', false)
            ->join('items', function ($join) {
                $join->on('items.id', '=', 'inventory_slots.item_id')
                    ->where('items.type', '!=', 'quest');
            })
            ->count();

        $gemBag = $this->gemBag;

        $gemAmount = 0;

        if (! is_null($gemBag)) {
            $gemAmount = GemBagSlot::where('gem_bag_id', $gemBag->id)->sum('amount');
        }

        return $slotCount + $gemAmount;
    }

    /**
     * Is the inventory full?
     */
    public function isInventoryFull(): bool
    {

        return $this->getInventoryCount() >= $this->inventory_max;
    }

    public function totalInventoryCount(): int
    {

        return $this->getInventoryCount();
    }

    protected static function newFactory()
    {
        return CharacterFactory::new();
    }
}
