<?php

namespace App\Flare\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\CharacterFactory;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Values\CharacterClassValue;

class Character extends Model {

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
        'killed_in_pvp',
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
        'inventory_max'               => 'integer',
        'can_attack'                  => 'boolean',
        'can_move'                    => 'boolean',
        'can_craft'                   => 'boolean',
        'can_spin'                    => 'boolean',
        'is_dead'                     => 'boolean',
        'force_name_change'           => 'boolean',
        'is_attack_automation_locked' => 'boolean',
        'killed_in_pvp'               => 'boolean',
        'can_engage_celestials'       => 'boolean',
        'can_move_again_at'           => 'datetime',
        'can_attack_again_at'         => 'datetime',
        'can_craft_again_at'          => 'datetime',
        'can_settle_again_at'         => 'datetime',
        'can_spin_again_at'           => 'datetime',
        'can_engage_celestials_again_at' => 'datetime',
        'level'                       => 'integer',
        'xp'                          => 'integer',
        'xp_next'                     => 'integer',
        'xp_penalty'                  => 'float',
        'str'                         => 'integer',
        'dur'                         => 'integer',
        'dex'                         => 'integer',
        'chr'                         => 'integer',
        'int'                         => 'integer',
        'agi'                         => 'integer',
        'focus'                       => 'integer',
        'ac'                          => 'integer',
        'gold'                        => 'integer',
        'gold_dust'                   => 'integer',
        'shards'                      => 'integer',
        'copper_coins'                => 'integer',
        'reincarnated_stat_increase'  => 'integer',
        'times_reincarnated'          => 'integer',
        'base_stat_mod'               => 'float',
        'base_damage_stat_mod'        => 'float',
    ];

    protected $appends = [
        'is_auto_battling',
    ];

    public function race() {
        return $this->belongsTo(GameRace::class, 'game_race_id', 'id');
    }

    public function class() {
        return $this->belongsTo(GameClass::class, 'game_class_id', 'id');
    }

    public function skills() {
        return $this->hasMany(Skill::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function inventory() {
        return $this->hasOne(Inventory::class, 'character_id', 'id');
    }

    public function inventorySets() {
        return $this->hasMany(InventorySet::class, 'character_id', 'id');
    }

    public function gemBag() {
        return $this->hasOne(GemBag::class, 'character_id', 'id');
    }

    public function factions() {
        return $this->hasMany(Faction::class, 'character_id', 'id');
    }

    public function rankTop() {
        return $this->hasOne(RankFightTop::class, 'character_id', 'id');
    }

    public function factionLoyalties() {
        return $this->hasMany(FactionLoyalty::class, 'character_id', 'id');
    }

    public function map() {
        return $this->hasOne(Map::class);
    }

    public function getXPositionAttribute() {
        return $this->map->character_position_x;
    }

    public function getYPositionAttribute() {
        return $this->map->character_position_y;
    }

    public function getMapUrlAttribute() {
        return $this->map->gameMap->path;
    }

    public function getKingdomsCountAttribute() {
        return $this->kingdoms->count();
    }

    public function kingdoms() {
        return $this->hasMany(Kingdom::class, 'character_id', 'id');
    }

    public function kingdomAttackLogs() {
        return $this->hasMany(KingdomLog::class);
    }

    public function unitMovementQueues() {
        return $this->hasMany(UnitMovementQueue::class);
    }

    public function boons() {
        return $this->hasMany(CharacterBoon::class);
    }

    public function questsCompleted() {
        return $this->hasMany(QuestsCompleted::class);
    }

    public function currentAutomations() {
        return $this->hasMany(CharacterAutomation::class);
    }

    public function passiveSkills() {
        return $this->hasMany(CharacterPassiveSkill::class);
    }

    public function classRanks() {
        return $this->hasMany(CharacterClassRank::class);
    }

    public function classSpecialsEquipped() {
        return $this->hasMany(CharacterClassSpecialtiesEquipped::class);
    }

    public function globalEventParticipation() {
        return $this->hasOne(GlobalEventParticipation::class, 'character_id', 'id');
    }

    public function globalEventKills() {
        return $this->hasOne(GlobalEventKill::class, 'character_id', 'id');
    }

    public function globalEventCrafts() {
        return $this->hasOne(GlobalEventCraft::class, 'character_id', 'id');
    }

    public function getIsAutoBattlingAttribute() {
        return !is_null(CharacterAutomation::where('character_id', $this->id)->first());
    }

    /**
     * Allows one to get specific information from a character.
     *
     * By returning the CharacterStatBuilder class, we can allow you to get
     * multiple calculated sets of data.
     *
     * @return CharacterStatBuilder
     */
    public function getInformation(): CharacterStatBuilder {
        $info = resolve(CharacterStatBuilder::class);

        return $info->setCharacter($this);
    }

    /**
     * Returns the character class value.
     *
     * @return CharacterClassValue
     * @throws Exception
     */
    public function classType(): CharacterClassValue {
        return new CharacterClassValue($this->class->name);
    }

    /**
     * Is the character logged in?
     *
     * @return boolean
     */
    public function isLoggedIn(): bool {
        return Session::where('user_id', $this->user_id)->exists();
    }

    /**
     * Gets the inventory count.
     *
     * Excludes quest and equipped items.
     *
     * @return int
     */
    public function getInventoryCount(): int {
        $inventory = Inventory::where('character_id', $this->id)->first();

        return InventorySlot::select('inventory_slots.*')
                            ->where('inventory_slots.inventory_id', $inventory->id)
                            ->where('inventory_slots.equipped', false)
                            ->join('items', function($join) {
                                $join->on('items.id', '=', 'inventory_slots.item_id')
                                     ->where('items.type', '!=', 'quest');
                           })->count() + $this->gemBag->gemSlots->sum('amount');
    }

    /**
     * Is the inventory full?
     *
     * @return bool
     */
    public function isInventoryFull(): bool {
        $gemCount = $this->gemBag->gemSlots->sum('amount');

        return ($this->getInventoryCount() + $gemCount) >= $this->inventory_max;
    }

    public function totalInventoryCount(): int {
        $gemCount = $this->gemBag->gemSlots->sum('amount');

        return $this->getInventoryCount() + $gemCount;
    }

    protected static function newFactory() {
        return CharacterFactory::new();
    }
}
