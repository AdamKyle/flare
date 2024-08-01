<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $message
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int|null $event_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Event|null $event
 * @method static \Database\Factories\AnnouncementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereUpdatedAt($value)
 */
	class Announcement extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $kingdom_id
 * @property int $building_id
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\KingdomBuilding|null $building
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingExpansionQueue whereUpdatedAt($value)
 */
	class BuildingExpansionQueue extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $kingdom_id
 * @property int $building_id
 * @property int $to_level
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $paid_with_gold
 * @property int|null $paid_amount
 * @property int $type
 * @property-read \App\Flare\Models\KingdomBuilding $building
 * @property-read \App\Flare\Models\Character $character
 * @property-read mixed $type_name
 * @property-read \App\Flare\Models\Kingdom $kingdom
 * @method static \Database\Factories\BuildingInQueueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue wherePaidWithGold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereToLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BuildingInQueue whereUpdatedAt($value)
 */
	class BuildingInQueue extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string|null $status
 * @property int $building_id
 * @property int $kingdom_id
 * @property int $request_kingdom_id
 * @property int $character_id
 * @property int $capital_city_building_queue_id
 * @property \Illuminate\Support\Carbon|null $travel_time_completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\KingdomBuilding|null $building
 * @property-read \App\Flare\Models\CapitalCityBuildingQueue|null $capitalCityBuildingQueue
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation query()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereCapitalCityBuildingQueueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereRequestKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereTravelTimeCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingCancellation whereUpdatedAt($value)
 */
	class CapitalCityBuildingCancellation extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $status
 * @property int $character_id
 * @property int $kingdom_id
 * @property int $requested_kingdom
 * @property array $building_request_data
 * @property array|null $messages
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @property-read \App\Flare\Models\Kingdom|null $requestingKingdom
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereBuildingRequestData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereRequestedKingdom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityBuildingQueue whereUpdatedAt($value)
 */
	class CapitalCityBuildingQueue extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string|null $status
 * @property int $unit_id
 * @property int $kingdom_id
 * @property int $request_kingdom_id
 * @property int $character_id
 * @property int $capital_city_unit_queue_id
 * @property \Illuminate\Support\Carbon|null $travel_time_completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\CapitalCityUnitQueue|null $capitalCityUnitQueue
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @property-read \App\Flare\Models\GameUnit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation query()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereCapitalCityUnitQueueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereRequestKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereTravelTimeCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitCancellation whereUpdatedAt($value)
 */
	class CapitalCityUnitCancellation extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $status
 * @property int $character_id
 * @property int $kingdom_id
 * @property int $requested_kingdom
 * @property array $unit_request_data
 * @property array|null $messages
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @property-read \App\Flare\Models\Kingdom|null $requestingKingdom
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereRequestedKingdom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereUnitRequestData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CapitalCityUnitQueue whereUpdatedAt($value)
 */
	class CapitalCityUnitQueue extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $monster_id
 * @property int|null $character_id
 * @property \Illuminate\Support\Carbon $conjured_at
 * @property int $x_position
 * @property int $y_position
 * @property bool $damaged_kingdom
 * @property bool $stole_treasury
 * @property bool $weakened_morale
 * @property int $current_health
 * @property int $max_health
 * @property int $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Monster|null $monster
 * @method static \Database\Factories\CelestialFightFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight query()
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereConjuredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereCurrentHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereDamagedKingdom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereMaxHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereMonsterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereStoleTreasury($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereWeakenedMorale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereXPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CelestialFight whereYPosition($value)
 */
	class CelestialFight extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $game_race_id
 * @property int $game_class_id
 * @property string $name
 * @property string $damage_stat
 * @property int|null $level
 * @property int $xp
 * @property int $xp_next
 * @property int $str
 * @property int $dur
 * @property int $dex
 * @property int $chr
 * @property int $int
 * @property int $focus
 * @property int $agi
 * @property int $ac
 * @property int|null $gold
 * @property int|null $inventory_max
 * @property bool|null $can_attack
 * @property bool|null $can_move
 * @property bool|null $can_craft
 * @property bool|null $is_dead
 * @property \Illuminate\Support\Carbon|null $can_move_again_at
 * @property \Illuminate\Support\Carbon|null $can_attack_again_at
 * @property \Illuminate\Support\Carbon|null $can_craft_again_at
 * @property bool|null $force_name_change
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $is_test
 * @property int|null $gold_dust
 * @property int|null $shards
 * @property int|null $current_adventure_id
 * @property bool $is_attack_automation_locked
 * @property int $is_mass_embezzling
 * @property \Illuminate\Support\Carbon|null $can_settle_again_at
 * @property int $copper_coins
 * @property bool $killed_in_pvp
 * @property \Illuminate\Support\Carbon|null $can_spin_again_at
 * @property bool $can_spin
 * @property int|null $is_mercenary_unlocked
 * @property \Illuminate\Support\Carbon|null $can_engage_celestials_again_at
 * @property bool|null $can_engage_celestials
 * @property float|null $xp_penalty
 * @property int|null $reincarnated_stat_increase
 * @property int|null $times_reincarnated
 * @property float $base_stat_mod
 * @property float $base_damage_stat_mod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CharacterBoon> $boons
 * @property-read int|null $boons_count
 * @property-read \App\Flare\Models\GameClass $class
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CharacterClassRank> $classRanks
 * @property-read int|null $class_ranks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CharacterClassSpecialtiesEquipped> $classSpecialsEquipped
 * @property-read int|null $class_specials_equipped_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CharacterAutomation> $currentAutomations
 * @property-read int|null $current_automations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\FactionLoyalty> $factionLoyalties
 * @property-read int|null $faction_loyalties_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Faction> $factions
 * @property-read int|null $factions_count
 * @property-read \App\Flare\Models\GemBag|null $gemBag
 * @property-read mixed $is_auto_battling
 * @property-read int|null $kingdoms_count
 * @property-read mixed $map_url
 * @property-read mixed $x_position
 * @property-read mixed $y_position
 * @property-read \App\Flare\Models\GlobalEventCraft|null $globalEventCrafts
 * @property-read \App\Flare\Models\GlobalEventEnchant|null $globalEventEnchants
 * @property-read \App\Flare\Models\GlobalEventKill|null $globalEventKills
 * @property-read \App\Flare\Models\GlobalEventParticipation|null $globalEventParticipation
 * @property-read \App\Flare\Models\Inventory|null $inventory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\InventorySet> $inventorySets
 * @property-read int|null $inventory_sets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\KingdomLog> $kingdomAttackLogs
 * @property-read int|null $kingdom_attack_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Kingdom> $kingdoms
 * @property-read \App\Flare\Models\Map|null $map
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CharacterPassiveSkill> $passiveSkills
 * @property-read int|null $passive_skills_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\QuestsCompleted> $questsCompleted
 * @property-read int|null $quests_completed_count
 * @property-read \App\Flare\Models\GameRace $race
 * @property-read \App\Flare\Models\RankFightTop|null $rankTop
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Skill> $skills
 * @property-read int|null $skills_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\UnitMovementQueue> $unitMovementQueues
 * @property-read int|null $unit_movement_queues_count
 * @property-read \App\Flare\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\WeeklyMonsterFight> $weeklyBattleFights
 * @property-read int|null $weekly_battle_fights_count
 * @method static \Database\Factories\CharacterFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Character newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Character newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Character query()
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereAc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereAgi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereBaseDamageStatMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereBaseStatMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanAttack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanAttackAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanCraft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanCraftAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanEngageCelestials($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanEngageCelestialsAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanMove($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanMoveAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanSettleAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanSpin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCanSpinAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereChr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCopperCoins($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereCurrentAdventureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereDamageStat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereDex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereDur($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereFocus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereForceNameChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereGameClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereGameRaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereGold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereGoldDust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereInt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereInventoryMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereIsAttackAutomationLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereIsDead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereIsMassEmbezzling($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereIsMercenaryUnlocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereIsTest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereKilledInPvp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereReincarnatedStatIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereShards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereTimesReincarnated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereXpNext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Character whereXpPenalty($value)
 */
	class Character extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int|null $monster_id
 * @property int $type
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon $completed_at
 * @property string|null $attack_type
 * @property int|null $move_down_monster_list_every
 * @property int|null $previous_level
 * @property int|null $current_level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\Monster|null $monster
 * @method static \Database\Factories\CharacterAutomationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereAttackType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereCurrentLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereMonsterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereMoveDownMonsterListEvery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation wherePreviousLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterAutomation whereUpdatedAt($value)
 */
	class CharacterAutomation extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $character_id
 * @property \Illuminate\Support\Carbon $started
 * @property \Illuminate\Support\Carbon $complete
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $item_id
 * @property int $last_for_minutes
 * @property int $amount_used
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Item $itemUsed
 * @method static \Database\Factories\CharacterBoonFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereAmountUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereLastForMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereStarted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterBoon whereUpdatedAt($value)
 */
	class CharacterBoon extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $game_class_id
 * @property int $current_xp
 * @property int $required_xp
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\GameClass $gameClass
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CharacterClassRankWeaponMastery> $weaponMasteries
 * @property-read int|null $weapon_masteries_count
 * @method static \Database\Factories\CharacterClassRankFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereCurrentXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereGameClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereRequiredXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRank whereUpdatedAt($value)
 */
	class CharacterClassRank extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_class_rank_id
 * @property int $weapon_type
 * @property int $current_xp
 * @property int $required_xp
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\CharacterClassRank|null $classRank
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereCharacterClassRankId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereCurrentXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereRequiredXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassRankWeaponMastery whereWeaponType($value)
 */
	class CharacterClassRankWeaponMastery extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $game_class_special_id
 * @property int $level
 * @property int $current_xp
 * @property int $required_xp
 * @property bool $equipped
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\GameClassSpecial $gameClassSpecial
 * @property-read mixed $affix_damage_reduction
 * @property-read mixed $base_ac_mod
 * @property-read mixed $base_damage_mod
 * @property-read mixed $base_damage_stat_increase
 * @property-read mixed $base_healing_mod
 * @property-read mixed $base_spell_damage_mod
 * @property-read mixed $healing_reduction
 * @property-read mixed $health_mod
 * @property-read mixed $increase_specialty_damage_per_level
 * @property-read mixed $resistance_reduction
 * @property-read mixed $skill_reduction
 * @property-read mixed $specialty_damage
 * @property-read mixed $spell_evasion
 * @method static \Database\Factories\CharacterClassSpecialtiesEquippedFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereCurrentXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereEquipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereGameClassSpecialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereRequiredXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterClassSpecialtiesEquipped whereUpdatedAt($value)
 */
	class CharacterClassSpecialtiesEquipped extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $celestial_fight_id
 * @property int $character_id
 * @property int $character_max_health
 * @property int $character_current_health
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\CharacterInCelestialFightFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereCelestialFightId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereCharacterCurrentHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereCharacterMaxHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterInCelestialFight whereUpdatedAt($value)
 */
	class CharacterInCelestialFight extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $passive_skill_id
 * @property int|null $parent_skill_id
 * @property int|null $unlocks_game_building_id
 * @property int|null $current_level
 * @property int|null $hours_to_next
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property bool $is_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CharacterPassiveSkill> $children
 * @property-read int|null $children_count
 * @property-read mixed $current_bonus
 * @property-read mixed $is_maxed_level
 * @property-read mixed $name
 * @property-read \App\Flare\Models\PassiveSkill $passiveSkill
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereCurrentLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereHoursToNext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereParentSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill wherePassiveSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereUnlocksGameBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CharacterPassiveSkill whereUpdatedAt($value)
 */
	class CharacterPassiveSkill extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $type
 * @property string $started_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $raid_id
 * @property array|null $event_goal_steps
 * @property string|null $current_event_goal_step
 * @property-read \App\Flare\Models\Announcement|null $announcement
 * @property-read \App\Flare\Models\Raid|null $raid
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCurrentEventGoalStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereEventGoalSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereRaidId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 */
	class Event extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $game_map_id
 * @property int|null $current_level
 * @property int|null $current_points
 * @property int|null $points_needed
 * @property string|null $title
 * @property bool $maxed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\GameMap $gameMap
 * @method static \Illuminate\Database\Eloquent\Builder|Faction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Faction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Faction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereCurrentLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereCurrentPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereMaxed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction wherePointsNeeded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Faction whereUpdatedAt($value)
 */
	class Faction extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $faction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_pledged
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\Faction $faction
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\FactionLoyaltyNpc> $factionLoyaltyNpcs
 * @property-read int|null $faction_loyalty_npcs_count
 * @method static \Database\Factories\FactionLoyaltyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty query()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty whereFactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty whereIsPledged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyalty whereUpdatedAt($value)
 */
	class FactionLoyalty extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $faction_loyalty_id
 * @property int $npc_id
 * @property int $current_level
 * @property int $max_level
 * @property int $next_level_fame
 * @property bool $currently_helping
 * @property float $kingdom_item_defence_bonus
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\FactionLoyalty $factionLoyalty
 * @property-read \App\Flare\Models\FactionLoyaltyNpcTask|null $factionLoyaltyNpcTasks
 * @property-read mixed $current_fame
 * @property-read mixed $current_kingdom_item_defence_bonus
 * @property-read \App\Flare\Models\Npc $npc
 * @method static \Database\Factories\FactionLoyaltyNpcFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc query()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereCurrentLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereCurrentlyHelping($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereFactionLoyaltyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereKingdomItemDefenceBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereNextLevelFame($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereNpcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpc whereUpdatedAt($value)
 */
	class FactionLoyaltyNpc extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $faction_loyalty_id
 * @property int $faction_loyalty_npc_id
 * @property array $fame_tasks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\FactionLoyalty $factionLoyalty
 * @property-read \App\Flare\Models\FactionLoyaltyNpc $factionLoyaltyNpc
 * @property-read mixed $current_amount
 * @method static \Database\Factories\FactionLoyaltyNpcTaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask whereFactionLoyaltyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask whereFactionLoyaltyNpcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask whereFameTasks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FactionLoyaltyNpcTask whereUpdatedAt($value)
 */
	class FactionLoyaltyNpcTask extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $max_level
 * @property int $base_durability
 * @property int $base_defence
 * @property int $required_population
 * @property int|null $units_per_level
 * @property int|null $only_at_level
 * @property bool $is_resource_building
 * @property bool $trains_units
 * @property bool $is_walls
 * @property bool $is_church
 * @property bool $is_farm
 * @property int $wood_cost
 * @property int $clay_cost
 * @property int $stone_cost
 * @property int $iron_cost
 * @property int|null $steel_cost
 * @property float $time_to_build
 * @property float $time_increase_amount
 * @property float $decrease_morale_amount
 * @property int $increase_population_amount
 * @property float $increase_morale_amount
 * @property float $increase_wood_amount
 * @property float $increase_clay_amount
 * @property float $increase_stone_amount
 * @property float $increase_iron_amount
 * @property float $increase_durability_amount
 * @property float $increase_defence_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_locked
 * @property int|null $passive_skill_id
 * @property int|null $level_required
 * @property bool|null $is_special
 * @property-read \App\Flare\Models\PassiveSkill|null $passive
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GameBuildingUnit> $units
 * @property-read int|null $units_count
 * @method static \Database\Factories\GameBuildingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereBaseDefence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereBaseDurability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereClayCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereDecreaseMoraleAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseClayAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseDefenceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseDurabilityAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseIronAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseMoraleAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreasePopulationAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseStoneAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIncreaseWoodAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIronCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIsChurch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIsFarm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIsResourceBuilding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIsSpecial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereIsWalls($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereLevelRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereOnlyAtLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding wherePassiveSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereRequiredPopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereSteelCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereStoneCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereTimeIncreaseAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereTimeToBuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereTrainsUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereUnitsPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuilding whereWoodCost($value)
 */
	class GameBuilding extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $game_building_id
 * @property int $game_unit_id
 * @property int $required_level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\GameBuilding $gameBuilding
 * @property-read \App\Flare\Models\GameUnit|null $gameUnit
 * @property-read mixed $building_name
 * @method static \Database\Factories\GameBuildingUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit whereGameBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit whereGameUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit whereRequiredLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameBuildingUnit whereUpdatedAt($value)
 */
	class GameBuildingUnit extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $damage_stat
 * @property string $to_hit_stat
 * @property int|null $str_mod
 * @property int|null $dur_mod
 * @property int|null $dex_mod
 * @property int|null $chr_mod
 * @property int|null $int_mod
 * @property float|null $accuracy_mod
 * @property float|null $dodge_mod
 * @property float|null $defense_mod
 * @property float|null $looting_mod
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $agi_mod
 * @property int|null $focus_mod
 * @property int|null $primary_required_class_id
 * @property int|null $secondary_required_class_id
 * @property int|null $primary_required_class_level
 * @property int|null $secondary_required_class_level
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GameSkill> $gameSkills
 * @property-read int|null $game_skills_count
 * @property-read GameClass|null $primaryClassRequired
 * @property-read GameClass|null $secondaryClassRequired
 * @method static \Database\Factories\GameClassFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereAccuracyMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereAgiMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereChrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereDamageStat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereDefenseMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereDexMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereDodgeMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereDurMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereFocusMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereIntMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereLootingMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass wherePrimaryRequiredClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass wherePrimaryRequiredClassLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereSecondaryRequiredClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereSecondaryRequiredClassLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereStrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereToHitStat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClass whereUpdatedAt($value)
 */
	class GameClass extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $game_class_id
 * @property string $name
 * @property string $description
 * @property int $requires_class_rank_level
 * @property int|null $specialty_damage
 * @property int|null $increase_specialty_damage_per_level
 * @property float|null $specialty_damage_uses_damage_stat_amount
 * @property float|null $base_damage_mod
 * @property float|null $base_ac_mod
 * @property float|null $base_healing_mod
 * @property float|null $base_spell_damage_mod
 * @property float|null $health_mod
 * @property float|null $base_damage_stat_increase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $attack_type_required
 * @property float|null $spell_evasion
 * @property float|null $affix_damage_reduction
 * @property float|null $healing_reduction
 * @property float|null $skill_reduction
 * @property float|null $resistance_reduction
 * @property-read \App\Flare\Models\GameClass $gameClass
 * @method static \Database\Factories\GameClassSpecialtiesFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereAffixDamageReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereAttackTypeRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereBaseAcMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereBaseDamageMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereBaseDamageStatIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereBaseHealingMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereBaseSpellDamageMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereGameClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereHealingReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereHealthMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereIncreaseSpecialtyDamagePerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereRequiresClassRankLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereResistanceReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereSkillReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereSpecialtyDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereSpecialtyDamageUsesDamageStatAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereSpellEvasion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameClassSpecial whereUpdatedAt($value)
 */
	class GameClassSpecial extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $path
 * @property string $kingdom_color
 * @property bool|null $default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property float|null $xp_bonus
 * @property float|null $skill_training_bonus
 * @property float|null $drop_chance_bonus
 * @property float|null $enemy_stat_bonus
 * @property float|null $character_attack_reduction
 * @property int|null $required_location_id
 * @property int|null $only_during_event_type
 * @property bool $can_traverse
 * @property-read mixed $map_required_item
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Map> $maps
 * @property-read int|null $maps_count
 * @property-read \App\Flare\Models\Location|null $requiredLocation
 * @method static \Database\Factories\GameMapFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereCanTraverse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereCharacterAttackReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereDropChanceBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereEnemyStatBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereKingdomColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereOnlyDuringEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereRequiredLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereSkillTrainingBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameMap whereXpBonus($value)
 */
	class GameMap extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int|null $str_mod
 * @property int|null $dur_mod
 * @property int|null $dex_mod
 * @property int|null $chr_mod
 * @property int|null $int_mod
 * @property float|null $accuracy_mod
 * @property float|null $dodge_mod
 * @property float|null $defense_mod
 * @property float|null $looting_mod
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $agi_mod
 * @property int|null $focus_mod
 * @method static \Database\Factories\GameRaceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereAccuracyMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereAgiMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereChrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereDefenseMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereDexMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereDodgeMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereDurMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereFocusMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereIntMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereLootingMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereStrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameRace whereUpdatedAt($value)
 */
	class GameRace extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $description
 * @property string $name
 * @property int $max_level
 * @property float|null $base_damage_mod_bonus_per_level
 * @property float|null $base_healing_mod_bonus_per_level
 * @property float|null $base_ac_mod_bonus_per_level
 * @property float|null $fight_time_out_mod_bonus_per_level
 * @property float|null $move_time_out_mod_bonus_per_level
 * @property bool|null $can_train
 * @property float|null $skill_bonus_per_level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $type
 * @property int $is_locked
 * @property int|null $game_class_id
 * @property float|null $unit_time_reduction
 * @property float|null $building_time_reduction
 * @property float|null $unit_movement_time_reduction
 * @property float|null $class_bonus
 * @property-read \App\Flare\Models\GameClass|null $gameClass
 * @method static \Database\Factories\GameSkillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereBaseAcModBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereBaseDamageModBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereBaseHealingModBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereBuildingTimeReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereCanTrain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereClassBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereFightTimeOutModBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereGameClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereMoveTimeOutModBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereSkillBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereUnitMovementTimeReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereUnitTimeReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameSkill whereUpdatedAt($value)
 */
	class GameSkill extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $attack
 * @property int $defence
 * @property bool|null $can_not_be_healed
 * @property bool|null $is_settler
 * @property float|null $reduces_morale_by
 * @property bool|null $can_heal
 * @property float|null $heal_percentage
 * @property bool|null $siege_weapon
 * @property bool|null $is_airship
 * @property bool|null $defender
 * @property bool|null $attacker
 * @property int|null $wood_cost
 * @property int|null $clay_cost
 * @property int|null $stone_cost
 * @property int|null $iron_cost
 * @property int|null $steel_cost
 * @property int|null $required_population
 * @property int $time_to_recruit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool|null $is_special
 * @method static \Database\Factories\GameUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereAttack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereAttacker($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereCanHeal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereCanNotBeHealed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereClayCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereDefence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereDefender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereHealPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereIronCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereIsAirship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereIsSettler($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereIsSpecial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereReducesMoraleBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereRequiredPopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereSiegeWeapon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereSteelCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereStoneCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereTimeToRecruit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GameUnit whereWoodCost($value)
 */
	class GameUnit extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $tier
 * @property int $primary_atonement_type
 * @property int $secondary_atonement_type
 * @property int $tertiary_atonement_type
 * @property float $primary_atonement_amount
 * @property float $secondary_atonement_amount
 * @property float $tertiary_atonement_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\GemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Gem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gem query()
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem wherePrimaryAtonementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem wherePrimaryAtonementType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereSecondaryAtonementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereSecondaryAtonementType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereTertiaryAtonementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereTertiaryAtonementType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gem whereUpdatedAt($value)
 */
	class Gem extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GemBagSlot> $gemSlots
 * @property-read int|null $gem_slots_count
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag query()
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBag whereUpdatedAt($value)
 */
	class GemBag extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $gem_bag_id
 * @property int $gem_id
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Gem|null $gem
 * @property-read \App\Flare\Models\GemBag $gemBag
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot whereGemBagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot whereGemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GemBagSlot whereUpdatedAt($value)
 */
	class GemBagSlot extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $global_event_goal_id
 * @property int $character_id
 * @property int $crafts
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\GlobalEventGoal|null $globalEventGoal
 * @method static \Database\Factories\GlobalEventCraftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft whereCrafts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft whereGlobalEventGoalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraft whereUpdatedAt($value)
 */
	class GlobalEventCraft extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $global_event_id
 * @property int $character_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GlobalEventCraftingInventorySlot> $craftingSlots
 * @property-read int|null $crafting_slots_count
 * @property-read \App\Flare\Models\GlobalEventGoal|null $globalEvent
 * @method static \Database\Factories\GlobalEventGoalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory whereGlobalEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventory whereUpdatedAt($value)
 */
	class GlobalEventCraftingInventory extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $global_event_crafting_inventory_id
 * @property int $item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\GlobalEventCraftingInventory|null $inventory
 * @property-read \App\Flare\Models\Item|null $item
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot whereGlobalEventCraftingInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventCraftingInventorySlot whereUpdatedAt($value)
 */
	class GlobalEventCraftingInventorySlot extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $global_event_goal_id
 * @property int $character_id
 * @property int $enchants
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\GlobalEventGoal|null $globalEventGoal
 * @method static \Database\Factories\GlobalEventEnchantingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant whereEnchants($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant whereGlobalEventGoalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventEnchant whereUpdatedAt($value)
 */
	class GlobalEventEnchant extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $max_kills
 * @property int $reward_every
 * @property int $next_reward_at
 * @property int $event_type
 * @property string $item_specialty_type_reward
 * @property bool $should_be_unique
 * @property int|null $unique_type
 * @property bool $should_be_mythic
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $max_crafts
 * @property int|null $max_enchants
 * @property-read int $total_crafts
 * @property-read int $total_enchants
 * @property-read int $total_kills
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GlobalEventCraft> $globalEventCrafts
 * @property-read int|null $global_event_crafts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GlobalEventEnchant> $globalEventEnchants
 * @property-read int|null $global_event_enchants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GlobalEventKill> $globalEventKills
 * @property-read int|null $global_event_kills_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\GlobalEventParticipation> $globalEventParticipation
 * @property-read int|null $global_event_participation_count
 * @method static \Database\Factories\GlobalEventGoalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereItemSpecialtyTypeReward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereMaxCrafts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereMaxEnchants($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereMaxKills($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereNextRewardAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereRewardEvery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereShouldBeMythic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereShouldBeUnique($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereUniqueType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventGoal whereUpdatedAt($value)
 */
	class GlobalEventGoal extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $global_event_goal_id
 * @property int $character_id
 * @property int $kills
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\GlobalEventGoal|null $globalEventGoal
 * @method static \Database\Factories\GlobalEventKillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill whereGlobalEventGoalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill whereKills($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventKill whereUpdatedAt($value)
 */
	class GlobalEventKill extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $global_event_goal_id
 * @property int $character_id
 * @property int|null $current_kills
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $current_crafts
 * @property int|null $current_enchants
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\GlobalEventGoal|null $globalEventGoal
 * @method static \Database\Factories\GlobalEventParticipationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation query()
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereCurrentCrafts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereCurrentEnchants($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereCurrentKills($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereGlobalEventGoalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GlobalEventParticipation whereUpdatedAt($value)
 */
	class GlobalEventParticipation extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $intro_text
 * @property string $instructions
 * @property int|null $required_level
 * @property int|null $required_skill
 * @property int|null $required_skill_level
 * @property int|null $required_faction_id
 * @property int|null $required_faction_level
 * @property int|null $required_game_map_id
 * @property int|null $required_quest_id
 * @property int|null $required_quest_item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $gold_dust_reward
 * @property int|null $shards_reward
 * @property int|null $required_kingdoms
 * @property int|null $required_kingdom_level
 * @property int|null $required_kingdom_units
 * @property int|null $required_passive_skill
 * @property int|null $required_passive_level
 * @property int|null $faction_points_per_kill
 * @property int|null $required_shards
 * @property int $xp_reward
 * @property int|null $gold_reward
 * @property int|null $required_gold_dust
 * @property int|null $required_gold
 * @property int|null $required_stats
 * @property int|null $required_str
 * @property int|null $required_dex
 * @property int|null $required_int
 * @property int|null $required_dur
 * @property int|null $required_chr
 * @property int|null $required_agi
 * @property int|null $required_focus
 * @property int|null $required_secondary_skill
 * @property int|null $required_secondary_skill_level
 * @property int|null $secondary_quest_item_id
 * @property int|null $required_skill_type
 * @property int|null $required_skill_type_level
 * @property int|null $required_class_specials_equipped
 * @property string|null $desktop_instructions
 * @property string|null $mobile_instructions
 * @property int|null $required_class_rank_level
 * @property int|null $required_kingdom_building_id
 * @property int|null $required_kingdom_building_level
 * @property int|null $required_gold_bars
 * @property int|null $parent_id
 * @property int|null $unlock_at_level
 * @property int|null $only_during_event
 * @property int|null $be_on_game_map
 * @property int|null $required_event_goal_participation
 * @property int|null $required_holy_stacks
 * @property int|null $required_attached_gems
 * @property int|null $required_copper_coins
 * @property string|null $required_specialty_type
 * @property int|null $required_fame_level
 * @property-read mixed $faction_name
 * @property-read mixed $game_map_name
 * @property-read mixed $kingdom_building_name
 * @property-read mixed $parent_quest_name
 * @property-read mixed $passive_name
 * @property-read mixed $quest_item_name
 * @property-read mixed $quest_name
 * @property-read mixed $required_to_be_on_game_map_name
 * @property-read mixed $secondary_quest_item_name
 * @property-read mixed $secondary_skill_name
 * @property-read mixed $skill_name
 * @property-read mixed $skill_type_name
 * @method static \Database\Factories\GuideQuestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest query()
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereBeOnGameMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereDesktopInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereFactionPointsPerKill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereGoldDustReward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereGoldReward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereIntroText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereMobileInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereOnlyDuringEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredAgi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredAttachedGems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredChr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredClassRankLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredClassSpecialsEquipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredCopperCoins($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredDex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredDur($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredEventGoalParticipation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredFactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredFactionLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredFameLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredFocus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredGold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredGoldBars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredGoldDust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredHolyStacks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredInt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredKingdomBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredKingdomBuildingLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredKingdomLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredKingdomUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredKingdoms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredPassiveLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredPassiveSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredQuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredQuestItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSecondarySkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSecondarySkillLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredShards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSkillLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSkillType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSkillTypeLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredSpecialtyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredStats($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereRequiredStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereSecondaryQuestItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereShardsReward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereUnlockAtLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuideQuest whereXpReward($value)
 */
	class GuideQuest extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $item_id
 * @property float|null $devouring_darkness_bonus
 * @property float|null $stat_increase_bonus
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Item|null $item
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack query()
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack whereDevouringDarknessBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack whereStatIncreaseBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HolyStack whereUpdatedAt($value)
 */
	class HolyStack extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $page_name
 * @property array $page_sections
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage query()
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage wherePageName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage wherePageSections($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InfoPage whereUpdatedAt($value)
 */
	class InfoPage extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\InventorySlot> $slots
 * @property-read int|null $slots_count
 * @method static \Database\Factories\InventoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereUpdatedAt($value)
 */
	class Inventory extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property bool $is_equipped
 * @property bool $can_be_equipped
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $name
 * @property-read \App\Flare\Models\Character $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\SetSlot> $slots
 * @property-read int|null $slots_count
 * @method static \Database\Factories\InventorySetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet query()
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereCanBeEquipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereIsEquipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySet whereUpdatedAt($value)
 */
	class InventorySet extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $inventory_id
 * @property int $item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool|null $equipped
 * @property string|null $position
 * @property-read \App\Flare\Models\Inventory $inventory
 * @property-read \App\Flare\Models\Item|null $item
 * @method static \Database\Factories\InventorySlotFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot query()
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot whereEquipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventorySlot whereUpdatedAt($value)
 */
	class InventorySlot extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $item_suffix_id
 * @property int|null $item_prefix_id
 * @property bool|null $market_sellable
 * @property string $name
 * @property string $type
 * @property string|null $description
 * @property string|null $default_position
 * @property int|null $base_damage
 * @property int|null $base_healing
 * @property int|null $base_ac
 * @property int|null $cost
 * @property float|null $base_damage_mod
 * @property float|null $base_healing_mod
 * @property float|null $base_ac_mod
 * @property float|null $str_mod
 * @property float|null $dur_mod
 * @property float|null $dex_mod
 * @property float|null $chr_mod
 * @property float|null $int_mod
 * @property string|null $effect
 * @property bool|null $can_craft
 * @property int|null $skill_level_required
 * @property int|null $skill_level_trivial
 * @property string|null $crafting_type
 * @property string|null $skill_name
 * @property float|null $skill_bonus
 * @property float|null $skill_training_bonus
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $can_drop
 * @property bool $craft_only
 * @property int|null $gold_dust_cost
 * @property int|null $shards_cost
 * @property bool $usable
 * @property bool $damages_kingdoms
 * @property float|null $kingdom_damage
 * @property int|null $lasts_for
 * @property bool|null $stat_increase
 * @property float|null $increase_stat_by
 * @property int|null $affects_skill_type
 * @property float|null $increase_skill_bonus_by
 * @property float|null $increase_skill_training_bonus_by
 * @property float|null $base_damage_mod_bonus
 * @property float|null $base_healing_mod_bonus
 * @property float|null $base_ac_mod_bonus
 * @property float|null $fight_time_out_mod_bonus
 * @property float|null $move_time_out_mod_bonus
 * @property float|null $spell_evasion
 * @property float|null $artifact_annulment
 * @property float|null $agi_mod
 * @property float|null $focus_mod
 * @property bool $can_resurrect
 * @property float|null $resurrection_chance
 * @property float|null $healing_reduction
 * @property float|null $affix_damage_reduction
 * @property float|null $devouring_light
 * @property float|null $devouring_darkness
 * @property int|null $parent_id
 * @property int|null $drop_location_id
 * @property float|null $xp_bonus
 * @property bool $ignores_caps
 * @property bool $can_use_on_other_items
 * @property int|null $holy_level
 * @property int|null $holy_stacks
 * @property float|null $ambush_chance
 * @property float|null $ambush_resistance
 * @property float|null $counter_chance
 * @property float|null $counter_resistance
 * @property bool $is_mythic
 * @property int|null $copper_coin_cost
 * @property string|null $specialty_type
 * @property int|null $gold_bars_cost
 * @property bool $can_stack
 * @property bool $gains_additional_level
 * @property int|null $unlocks_class_id
 * @property int|null $socket_count
 * @property bool $has_gems_socketed
 * @property int|null $item_skill_id
 * @property string|null $alchemy_type
 * @property bool|null $is_cosmic
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\HolyStack> $appliedHolyStacks
 * @property-read int|null $applied_holy_stacks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Item> $children
 * @property-read int|null $children_count
 * @property-read \App\Flare\Models\Location|null $dropLocation
 * @property-read float $affix
 * @property-read mixed $affix_count
 * @property-read mixed $affix_name
 * @property-read mixed $holy_stack_devouring_darkness
 * @property-read mixed $holy_stack_stat_bonus
 * @property-read mixed $holy_stacks_applied
 * @property-read mixed $is_unique
 * @property-read mixed $locations
 * @property-read mixed $required_monster
 * @property-read mixed $required_quest
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\SetSlot> $inventorySetSlots
 * @property-read int|null $inventory_set_slots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\InventorySlot> $inventorySlots
 * @property-read int|null $inventory_slots_count
 * @property-read \App\Flare\Models\ItemAffix|null $itemPrefix
 * @property-read \App\Flare\Models\ItemSkill|null $itemSkill
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\ItemSkillProgression> $itemSkillProgressions
 * @property-read int|null $item_skill_progressions_count
 * @property-read \App\Flare\Models\ItemAffix|null $itemSuffix
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\MarketHistory> $marketHistory
 * @property-read int|null $market_history_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\MarketBoard> $marketListings
 * @property-read int|null $market_listings_count
 * @property-read Item|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\ItemSocket> $sockets
 * @property-read int|null $sockets_count
 * @property-read \App\Flare\Models\GameClass|null $unlocksClass
 * @method static \Database\Factories\ItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Item getItemSkills()
 * @method static \Illuminate\Database\Eloquent\Builder|Item getTotalBaseDamageMod()
 * @method static \Illuminate\Database\Eloquent\Builder|Item getTotalDamage()
 * @method static \Illuminate\Database\Eloquent\Builder|Item getTotalDefence()
 * @method static \Illuminate\Database\Eloquent\Builder|Item getTotalFightTimeOutMod()
 * @method static \Illuminate\Database\Eloquent\Builder|Item getTotalHealing()
 * @method static \Illuminate\Database\Eloquent\Builder|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Item newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereAffectsSkillType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereAffixDamageReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereAgiMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereAlchemyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereAmbushChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereAmbushResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereArtifactAnnulment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseAc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseAcMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseAcModBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseDamageMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseDamageModBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseHealing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseHealingMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereBaseHealingModBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCanCraft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCanDrop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCanResurrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCanStack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCanUseOnOtherItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereChrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCopperCoinCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCounterChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCounterResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCraftOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCraftingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDamagesKingdoms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDefaultPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDevouringDarkness($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDevouringLight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDexMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDropLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDurMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereEffect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereFightTimeOutModBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereFocusMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereGainsAdditionalLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereGoldBarsCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereGoldDustCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereHasGemsSocketed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereHealingReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereHolyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereHolyStacks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIgnoresCaps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIncreaseSkillBonusBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIncreaseSkillTrainingBonusBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIncreaseStatBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIntMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIsCosmic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereIsMythic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereItemPrefixId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereItemSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereItemSuffixId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereKingdomDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereLastsFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereMarketSellable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereMoveTimeOutModBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereResurrectionChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereShardsCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSkillBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSkillLevelRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSkillLevelTrivial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSkillName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSkillTrainingBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSocketCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSpecialtyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSpellEvasion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereStatIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereStrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereUnlocksClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereUsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereXpBonus($value)
 */
	class Item extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float|null $base_damage_mod
 * @property float|null $base_healing_mod
 * @property float|null $base_ac_mod
 * @property float|null $str_mod
 * @property float|null $dur_mod
 * @property float|null $dex_mod
 * @property float|null $chr_mod
 * @property float|null $int_mod
 * @property int|null $int_required
 * @property int|null $skill_level_required
 * @property int|null $skill_level_trivial
 * @property string|null $skill_name
 * @property float|null $skill_bonus
 * @property float|null $skill_training_bonus
 * @property int $cost
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $can_drop
 * @property float|null $agi_mod
 * @property float|null $focus_mod
 * @property int|null $affects_skill_type
 * @property bool $damage_can_stack
 * @property bool $irresistible_damage
 * @property float|null $str_reduction
 * @property float|null $dur_reduction
 * @property float|null $dex_reduction
 * @property float|null $chr_reduction
 * @property float|null $int_reduction
 * @property float|null $agi_reduction
 * @property float|null $focus_reduction
 * @property float|null $steal_life_amount
 * @property float|null $entranced_chance
 * @property float $reduces_enemy_stats
 * @property float|null $devouring_light
 * @property float|null $skill_reduction
 * @property float|null $resistance_reduction
 * @property bool $randomly_generated
 * @property int $affix_type
 * @property float|null $damage_amount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Item> $itemsWithPrefix
 * @property-read int|null $items_with_prefix_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Item> $itemsWithSuffix
 * @property-read int|null $items_with_suffix_count
 * @method static \Database\Factories\ItemAffixFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix getOppositeType()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix query()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereAffectsSkillType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereAffixType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereAgiMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereAgiReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereBaseAcMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereBaseDamageMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereBaseHealingMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereCanDrop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereChrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereChrReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDamageAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDamageCanStack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDevouringLight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDexMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDexReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDurMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereDurReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereEntrancedChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereFocusMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereFocusReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereIntMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereIntReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereIntRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereIrresistibleDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereRandomlyGenerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereReducesEnemyStats($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereResistanceReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereSkillBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereSkillLevelRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereSkillLevelTrivial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereSkillName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereSkillReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereSkillTrainingBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereStealLifeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereStrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereStrReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemAffix whereUpdatedAt($value)
 */
	class ItemAffix extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property float|null $str_mod
 * @property float|null $dex_mod
 * @property float|null $dur_mod
 * @property float|null $chr_mod
 * @property float|null $focus_mod
 * @property float|null $int_mod
 * @property float|null $agi_mod
 * @property float|null $base_damage_mod
 * @property float|null $base_ac_mod
 * @property float|null $base_healing_mod
 * @property int $max_level
 * @property int $total_kills_needed
 * @property int|null $parent_level_needed
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ItemSkill> $children
 * @property-read int|null $children_count
 * @property-read ItemSkill|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereAgiMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereBaseAcMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereBaseDamageMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereBaseHealingMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereChrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereDexMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereDurMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereFocusMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereIntMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereParentLevelNeeded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereStrMod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereTotalKillsNeeded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkill whereUpdatedAt($value)
 */
	class ItemSkill extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $item_id
 * @property int $item_skill_id
 * @property int $current_level
 * @property int $current_kill
 * @property bool $is_training
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $agi_mod
 * @property-read mixed $base_ac_mod
 * @property-read mixed $base_damage_mod
 * @property-read mixed $base_healing_mod
 * @property-read mixed $chr_mod
 * @property-read mixed $dex_mod
 * @property-read mixed $dur_mod
 * @property-read mixed $focus_mod
 * @property-read mixed $int_mod
 * @property-read mixed $str_mod
 * @property-read \App\Flare\Models\Item|null $item
 * @property-read \App\Flare\Models\ItemSkill|null $itemSkill
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression query()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereCurrentKill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereCurrentLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereIsTraining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereItemSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSkillProgression whereUpdatedAt($value)
 */
	class ItemSkillProgression extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $item_id
 * @property int|null $gem_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Gem|null $gem
 * @property-read \App\Flare\Models\Item $item
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket query()
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket whereGemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ItemSocket whereUpdatedAt($value)
 */
	class ItemSocket extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $character_id
 * @property int $game_map_id
 * @property string $name
 * @property array $color
 * @property int $max_stone
 * @property int $max_wood
 * @property int $max_clay
 * @property int $max_iron
 * @property int $current_stone
 * @property int $current_wood
 * @property int $current_clay
 * @property int $current_iron
 * @property int $current_population
 * @property int $max_population
 * @property int $x_position
 * @property int $y_position
 * @property float $current_morale
 * @property float $max_morale
 * @property int|null $treasury
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $last_walked
 * @property bool $npc_owned
 * @property int|null $gold_bars
 * @property \Illuminate\Support\Carbon|null $protected_until
 * @property int|null $max_steel
 * @property int|null $current_steel
 * @property bool $is_capital
 * @property bool $auto_walked
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\KingdomBuilding> $buildings
 * @property-read int|null $buildings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\BuildingInQueue> $buildingsQueue
 * @property-read int|null $buildings_queue_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\CapitalCityBuildingQueue> $capitalCityBuildingQueue
 * @property-read int|null $capital_city_building_queue_count
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\GameMap $gameMap
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\KingdomUnit> $units
 * @property-read int|null $units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\UnitMovementQueue> $unitsMovementQueue
 * @property-read int|null $units_movement_queue_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\UnitInQueue> $unitsQueue
 * @property-read int|null $units_queue_count
 * @method static \Database\Factories\KingdomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom query()
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereAutoWalked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentClay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentIron($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentMorale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentPopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentSteel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentStone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereCurrentWood($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereGoldBars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereIsCapital($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereLastWalked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxClay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxIron($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxMorale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxPopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxSteel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxStone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereMaxWood($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereNpcOwned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereProtectedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereTreasury($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereXPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kingdom whereYPosition($value)
 */
	class Kingdom extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $game_building_id
 * @property int $kingdom_id
 * @property int $level
 * @property int $max_defence
 * @property int $max_durability
 * @property int $current_defence
 * @property int $current_durability
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_locked
 * @property-read \App\Flare\Models\KingdomBuildingExpansion|null $buildingExpansion
 * @property-read \App\Flare\Models\GameBuilding $gameBuilding
 * @property-read mixed $base_clay_cost
 * @property-read mixed $base_iron_cost
 * @property-read mixed $base_population
 * @property-read mixed $base_steel_cost
 * @property-read mixed $base_stone_cost
 * @property-read mixed $base_wood_cost
 * @property-read mixed $clay_cost
 * @property-read mixed $defence
 * @property-read mixed $description
 * @property-read mixed $durability
 * @property-read mixed $future_defence
 * @property-read mixed $future_durability
 * @property-read mixed $future_increase_in_clay
 * @property-read mixed $future_increase_in_iron
 * @property-read mixed $future_increase_in_stone
 * @property-read mixed $future_increase_in_wood
 * @property-read mixed $future_population_increase
 * @property-read mixed $gives_resources
 * @property-read mixed $increase_in_clay
 * @property-read mixed $increase_in_iron
 * @property-read mixed $increase_in_stone
 * @property-read mixed $increase_in_wood
 * @property-read mixed $iron_cost
 * @property-read mixed $is_at_max_level
 * @property-read mixed $is_church
 * @property-read mixed $is_farm
 * @property-read mixed $is_walls
 * @property-read mixed $morale_decrease
 * @property-read mixed $morale_increase
 * @property-read mixed $name
 * @property-read mixed $population_increase
 * @property-read mixed $rebuild_time
 * @property-read mixed $required_population
 * @property-read mixed $steel_cost
 * @property-read mixed $stone_cost
 * @property-read mixed $time_increase
 * @property-read mixed $trains_units
 * @property-read mixed $wood_cost
 * @property-read \App\Flare\Models\Kingdom $kingdom
 * @method static \Database\Factories\KingdomBuildingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding query()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereCurrentDefence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereCurrentDurability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereGameBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereMaxDefence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereMaxDurability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuilding whereUpdatedAt($value)
 */
	class KingdomBuilding extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $kingdom_building_id
 * @property int $kingdom_id
 * @property int $expansion_type
 * @property int $expansion_count
 * @property int $expansions_left
 * @property int $minutes_until_next_expansion
 * @property array $resource_costs
 * @property int $gold_bars_cost
 * @property array $resource_increases
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @property-read \App\Flare\Models\KingdomBuilding|null $kingdomBuilding
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion query()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereExpansionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereExpansionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereExpansionsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereGoldBarsCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereKingdomBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereMinutesUntilNextExpansion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereResourceCosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereResourceIncreases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomBuildingExpansion whereUpdatedAt($value)
 */
	class KingdomBuildingExpansion extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int|null $from_kingdom_id
 * @property int|null $to_kingdom_id
 * @property int $status
 * @property array|null $units_sent
 * @property array|null $units_survived
 * @property bool $published
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $opened
 * @property array|null $old_buildings
 * @property array|null $new_buildings
 * @property array|null $old_units
 * @property array|null $new_units
 * @property float|null $morale_loss
 * @property float|null $item_damage
 * @property int|null $attacking_character_id
 * @property array|null $additional_details
 * @property-read \App\Flare\Models\Character $character
 * @property-read mixed $from_kingdom
 * @property-read mixed $to_kingdom
 * @property-write mixed $new_buildings_units
 * @property-write mixed $new_units_units
 * @method static \Database\Factories\KingdomLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereAdditionalDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereAttackingCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereFromKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereItemDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereMoraleLoss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereNewBuildings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereNewUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereOldBuildings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereOldUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereOpened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereToKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereUnitsSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereUnitsSurvived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomLog whereUpdatedAt($value)
 */
	class KingdomLog extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $kingdom_id
 * @property int $game_unit_id
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\GameUnit|null $gameUnit
 * @property-read \App\Flare\Models\Kingdom $kingdom
 * @method static \Database\Factories\KingdomUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit whereGameUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KingdomUnit whereUpdatedAt($value)
 */
	class KingdomUnit extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $quest_reward_item_id
 * @property int $x
 * @property int $y
 * @property string $name
 * @property string $description
 * @property bool|null $is_port
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $game_map_id
 * @property int|null $enemy_strength_type
 * @property int|null $required_quest_item_id
 * @property int|null $type
 * @property bool $can_players_enter
 * @property bool $can_auto_battle
 * @property int|null $raid_id
 * @property bool $has_raid_boss
 * @property bool $is_corrupted
 * @property string|null $pin_css_class
 * @property-read \App\Flare\Models\GameMap|null $map
 * @property-read \App\Flare\Models\Item|null $questRewardItem
 * @property-read \App\Flare\Models\Raid|null $raid
 * @property-read \App\Flare\Models\Item|null $requiredQuestItem
 * @method static \Database\Factories\LocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereCanAutoBattle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereCanPlayersEnter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereEnemyStrengthType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereHasRaidBoss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereIsCorrupted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereIsPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location wherePinCssClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereQuestRewardItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereRaidId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereRequiredQuestItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Location whereY($value)
 */
	class Location extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int|null $position_x
 * @property int|null $position_y
 * @property int|null $character_position_x
 * @property int|null $character_position_y
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $game_map_id
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\GameMap|null $gameMap
 * @method static \Database\Factories\MapFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Map newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Map newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Map query()
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereCharacterPositionX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereCharacterPositionY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map wherePositionX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map wherePositionY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Map whereUpdatedAt($value)
 */
	class Map extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $item_id
 * @property int $listed_price
 * @property bool|null $is_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Item $item
 * @method static \Database\Factories\MarketBoardFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard query()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereListedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketBoard whereUpdatedAt($value)
 */
	class MarketBoard extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $item_id
 * @property int $sold_for
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Item $item
 * @method static \Database\Factories\MarketHistoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory whereSoldFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketHistory whereUpdatedAt($value)
 */
	class MarketHistory extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $max_level
 * @property int $half_way
 * @property int $three_quarters
 * @property int $last_leg
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\MaxLevelConfigurationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereHalfWay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereLastLeg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereThreeQuarters($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaxLevelConfiguration whereUpdatedAt($value)
 */
	class MaxLevelConfiguration extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $str
 * @property int $dur
 * @property int $dex
 * @property int $chr
 * @property int $int
 * @property int $agi
 * @property int $focus
 * @property int $ac
 * @property int|null $max_level
 * @property string $damage_stat
 * @property int $xp
 * @property float $drop_check
 * @property int|null $gold
 * @property string $health_range
 * @property string $attack_range
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $quest_item_id
 * @property float|null $quest_item_drop_chance
 * @property int|null $game_map_id
 * @property bool $is_celestial_entity
 * @property int|null $gold_cost
 * @property int|null $gold_dust_cost
 * @property bool $can_cast
 * @property int|null $max_spell_damage
 * @property int|null $shards
 * @property float|null $spell_evasion
 * @property float|null $affix_resistance
 * @property int|null $max_affix_damage
 * @property float|null $healing_percentage
 * @property float|null $entrancing_chance
 * @property float|null $devouring_light_chance
 * @property float|null $accuracy
 * @property float|null $casting_accuracy
 * @property float|null $dodge
 * @property float|null $criticality
 * @property float|null $devouring_darkness_chance
 * @property float|null $ambush_chance
 * @property float|null $ambush_resistance
 * @property float|null $counter_chance
 * @property float|null $counter_resistance
 * @property int|null $celestial_type
 * @property bool $is_raid_monster
 * @property bool $is_raid_boss
 * @property int|null $raid_special_attack_type
 * @property float|null $fire_atonement
 * @property float|null $ice_atonement
 * @property float|null $water_atonement
 * @property float|null $life_stealing_resistance
 * @property int|null $only_for_location_type
 * @property-read \App\Flare\Models\GameMap|null $gameMap
 * @property-read \App\Flare\Models\Item|null $questItem
 * @method static \Database\Factories\MonsterFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Monster newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Monster newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Monster query()
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAffixResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAgi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAmbushChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAmbushResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereAttackRange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCanCast($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCastingAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCelestialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereChr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCounterChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCounterResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereCriticality($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDamageStat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDevouringDarknessChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDevouringLightChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDodge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDropCheck($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereDur($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereEntrancingChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereFireAtonement($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereFocus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereGold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereGoldCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereGoldDustCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereHealingPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereHealthRange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereIceAtonement($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereInt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereIsCelestialEntity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereIsRaidBoss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereIsRaidMonster($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereLifeStealingResistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereMaxAffixDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereMaxSpellDamage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereOnlyForLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereQuestItemDropChance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereQuestItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereRaidSpecialAttackType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereShards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereSpellEvasion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereWaterAtonement($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Monster whereXp($value)
 */
	class Monster extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $attack_type
 * @property-read \App\Flare\Models\Character $character
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant whereAttackType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MonthlyPvpParticipant whereUpdatedAt($value)
 */
	class MonthlyPvpParticipant extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $game_map_id
 * @property string $name
 * @property string $real_name
 * @property int $type
 * @property int $x_position
 * @property int $y_position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\GameMap|null $gameMap
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\Quest> $quests
 * @property-read int|null $quests_count
 * @method static \Database\Factories\NpcFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Npc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Npc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Npc query()
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereRealName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereXPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Npc whereYPosition($value)
 */
	class Npc extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $max_level
 * @property float|null $bonus_per_level
 * @property int $effect_type
 * @property int|null $parent_skill_id
 * @property int|null $unlocks_at_level
 * @property bool $is_locked
 * @property bool $is_parent
 * @property int $hours_per_level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $resource_bonus_per_level
 * @property float|null $capital_city_building_request_travel_time_reduction
 * @property float|null $capital_city_unit_request_travel_time_reduction
 * @property float|null $resource_request_time_reduction
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PassiveSkill> $childSkills
 * @property-read int|null $child_skills_count
 * @property-read \App\Flare\Models\GameBuilding|null $gameBuilding
 * @property-read PassiveSkill|null $parent
 * @method static \Database\Factories\PassiveSkillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereCapitalCityBuildingRequestTravelTimeReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereCapitalCityUnitRequestTravelTimeReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereEffectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereHoursPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereIsParent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereParentSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereResourceBonusPerLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereResourceRequestTimeReduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereUnlocksAtLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PassiveSkill whereUpdatedAt($value)
 */
	class PassiveSkill extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $npc_id
 * @property int|null $item_id
 * @property int|null $gold_dust_cost
 * @property int|null $shard_cost
 * @property int|null $gold_cost
 * @property int|null $reward_item
 * @property int|null $reward_gold_dust
 * @property int|null $reward_shards
 * @property int|null $reward_gold
 * @property int|null $reward_xp
 * @property bool $unlocks_skill
 * @property int|null $unlocks_skill_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_parent
 * @property int|null $parent_quest_id
 * @property int|null $secondary_required_item
 * @property int|null $faction_game_map_id
 * @property int|null $required_faction_level
 * @property int|null $access_to_map_id
 * @property int|null $copper_coin_cost
 * @property string|null $before_completion_description
 * @property string|null $after_completion_description
 * @property int|null $unlocks_feature
 * @property int|null $unlocks_passive_id
 * @property int|null $raid_id
 * @property int|null $required_quest_id
 * @property int|null $reincarnated_times
 * @property int|null $only_for_event
 * @property int|null $assisting_npc_id
 * @property int|null $required_fame_level
 * @property int|null $parent_chain_quest_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Quest> $childQuests
 * @property-read int|null $child_quests_count
 * @property-read \App\Flare\Models\Npc|null $factionLoyaltyNpc
 * @property-read \App\Flare\Models\GameMap|null $factionMap
 * @property-read mixed $belongs_to_map_name
 * @property-read mixed $required_item_monster
 * @property-read mixed $unlocks_skill_name
 * @property-read \App\Flare\Models\Item|null $item
 * @property-read \App\Flare\Models\Npc $npc
 * @property-read Quest|null $parent
 * @property-read Quest|null $parentChainQuest
 * @property-read \App\Flare\Models\PassiveSkill|null $passive
 * @property-read \App\Flare\Models\Raid|null $raid
 * @property-read \App\Flare\Models\GameMap|null $requiredPlane
 * @property-read Quest|null $requiredQuest
 * @property-read \App\Flare\Models\Item|null $rewardItem
 * @property-read \App\Flare\Models\Item|null $secondaryItem
 * @method static \Database\Factories\QuestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Quest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Quest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Quest query()
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereAccessToMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereAfterCompletionDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereAssistingNpcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereBeforeCompletionDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereCopperCoinCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereFactionGameMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereGoldCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereGoldDustCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereIsParent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereNpcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereOnlyForEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereParentChainQuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereParentQuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRaidId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereReincarnatedTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRequiredFactionLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRequiredFameLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRequiredQuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRewardGold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRewardGoldDust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRewardItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRewardShards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereRewardXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereSecondaryRequiredItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereShardCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereUnlocksFeature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereUnlocksPassiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereUnlocksSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereUnlocksSkillType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Quest whereUpdatedAt($value)
 */
	class Quest extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $character_id
 * @property int|null $quest_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $guide_quest_id
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\GuideQuest|null $guideQuest
 * @property-read \App\Flare\Models\Quest|null $quest
 * @method static \Database\Factories\QuestsCompletedFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted query()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted whereGuideQuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted whereQuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestsCompleted whereUpdatedAt($value)
 */
	class QuestsCompleted extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $story
 * @property int $raid_boss_id
 * @property array $raid_monster_ids
 * @property int $raid_boss_location_id
 * @property array $corrupted_location_ids
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $item_specialty_reward_type
 * @property int|null $artifact_item_id
 * @property-read \App\Flare\Models\Item|null $artifactItem
 * @property-read \App\Flare\Models\Monster|null $raidBoss
 * @property-read \App\Flare\Models\Location|null $raidBossLocation
 * @method static \Database\Factories\RaidFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Raid newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Raid newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Raid query()
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereArtifactItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereCorruptedLocationIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereItemSpecialtyRewardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereRaidBossId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereRaidBossLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereRaidMonsterIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereStory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Raid whereUpdatedAt($value)
 */
	class Raid extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $raid_id
 * @property int $raid_boss_id
 * @property int|null $boss_max_hp
 * @property int|null $boss_current_hp
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array|null $raid_boss_deatils
 * @property-read \App\Flare\Models\Raid|null $raid
 * @property-read \App\Flare\Models\Monster|null $raidBoss
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss query()
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereBossCurrentHp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereBossMaxHp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereRaidBossDeatils($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereRaidBossId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereRaidId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBoss whereUpdatedAt($value)
 */
	class RaidBoss extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $raid_id
 * @property int $attacks_left
 * @property int $damage_dealt
 * @property bool $killed_boss
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Raid|null $raid
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation query()
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereAttacksLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereDamageDealt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereKilledBoss($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereRaidId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RaidBossParticipation whereUpdatedAt($value)
 */
	class RaidBossParticipation extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $current_rank
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight query()
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight whereCurrentRank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFight whereUpdatedAt($value)
 */
	class RankFight extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $current_rank
 * @property \Illuminate\Support\Carbon $rank_achievement_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character $character
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop query()
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop whereCurrentRank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop whereRankAchievementDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RankFightTop whereUpdatedAt($value)
 */
	class RankFightTop extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $version
 * @property \Illuminate\Support\Carbon $release_date
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ReleaseNoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereReleaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReleaseNote whereVersion($value)
 */
	class ReleaseNote extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Flare\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role withoutPermission($permissions)
 */
	class Role extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $event_type
 * @property int|null $raid_id
 * @property string $description
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $currently_running
 * @property-read \App\Flare\Models\Raid|null $raid
 * @method static \Database\Factories\ScheduledEventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereCurrentlyRunning($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereRaidId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEvent whereUpdatedAt($value)
 */
	class ScheduledEvent extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $event_type
 * @property \Illuminate\Support\Carbon $start_date
 * @property string $generate_every
 * @property \Illuminate\Support\Carbon|null $last_time_generated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereGenerateEvery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereLastTimeGenerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduledEventConfiguration whereUpdatedAt($value)
 */
	class ScheduledEventConfiguration extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $payload
 * @property int $last_activity
 * @method static \Illuminate\Database\Eloquent\Builder|Session newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Session newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Session query()
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereLastActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereUserId($value)
 */
	class Session extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $item_id
 * @property int $inventory_set_id
 * @property int $equipped
 * @property string|null $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\InventorySet $inventorySet
 * @property-read \App\Flare\Models\Item|null $item
 * @method static \Database\Factories\SetSlotFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot whereEquipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot whereInventorySetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SetSlot whereUpdatedAt($value)
 */
	class SetSlot extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $character_id
 * @property bool|null $currently_training
 * @property int $level
 * @property int|null $xp
 * @property int|null $xp_max
 * @property float|null $xp_towards
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $game_skill_id
 * @property bool $is_locked
 * @property int|null $skill_type
 * @property bool|null $is_hidden
 * @property-read \App\Flare\Models\GameSkill $baseSkill
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read mixed $base_a_c_mod
 * @property-read mixed $base_damage_mod
 * @property-read mixed $base_healing_mod
 * @property-read mixed $building_time_reduction
 * @property-read mixed $can_train
 * @property-read mixed $class_bonus
 * @property-read mixed $class_id
 * @property-read mixed $description
 * @property-read mixed $fight_time_out_mod
 * @property-read mixed $max_level
 * @property-read mixed $move_time_out_mod
 * @property-read mixed $name
 * @property-read mixed $reduces_movement_time
 * @property-read mixed $reduces_time
 * @property-read mixed $skill_bonus
 * @property-read mixed $skill_training_bonus
 * @property-read mixed $unit_movement_time_reduction
 * @property-read mixed $unit_time_reduction
 * @method static \Database\Factories\SkillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Skill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Skill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Skill query()
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereCurrentlyTraining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereGameSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereSkillType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereXpMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Skill whereXpTowards($value)
 */
	class Skill extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $kingdom_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon $completed_at
 * @property int $amount_to_smelt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Kingdom|null $kingdom
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress query()
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereAmountToSmelt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmeltingProgress whereUpdatedAt($value)
 */
	class SmeltingProgress extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $kingdom_id
 * @property int $game_unit_id
 * @property int $amount
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $gold_paid
 * @property-read \App\Flare\Models\Character $character
 * @property-read \App\Flare\Models\Kingdom $kingdom
 * @property-read \App\Flare\Models\GameUnit $unit
 * @method static \Database\Factories\UnitInQueueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereGameUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereGoldPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitInQueue whereUpdatedAt($value)
 */
	class UnitInQueue extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $from_kingdom_id
 * @property int $to_kingdom_id
 * @property array $units_moving
 * @property \Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property int $moving_to_x
 * @property int $moving_to_y
 * @property int $from_x
 * @property int $from_y
 * @property bool|null $is_attacking
 * @property bool|null $is_recalled
 * @property bool|null $is_returning
 * @property bool $is_moving
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $resources_requested
 * @property-read \App\Flare\Models\Character $character
 * @property-read mixed $from_kingdom
 * @property-read mixed $to_kingdom
 * @method static \Database\Factories\UnitMoveQueueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereFromKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereFromX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereFromY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereIsAttacking($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereIsMoving($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereIsRecalled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereIsReturning($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereMovingToX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereMovingToY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereResourcesRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereToKingdomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereUnitsMoving($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UnitMovementQueue whereUpdatedAt($value)
 */
	class UnitMovementQueue extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property int|null $message_throttle_count
 * @property \Illuminate\Support\Carbon|null $can_speak_again_at
 * @property bool $is_silenced
 * @property string|null $ip_address
 * @property bool $is_banned
 * @property \Illuminate\Support\Carbon|null $unbanned_at
 * @property \Illuminate\Support\Carbon|null $timeout_until
 * @property string|null $banned_reason
 * @property string|null $un_ban_request
 * @property int $upgraded_building_email
 * @property int $rebuilt_building_email
 * @property int $kingdom_attack_email
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $show_unit_recruitment_messages
 * @property bool $show_building_upgrade_messages
 * @property bool $show_building_rebuilt_messages
 * @property bool $show_kingdom_update_messages
 * @property bool $auto_disenchant
 * @property string|null $auto_disenchant_amount
 * @property \Illuminate\Support\Carbon|null $last_logged_in
 * @property bool $will_be_deleted
 * @property bool $ignored_unban_request
 * @property bool $guide_enabled
 * @property string|null $chat_text_color
 * @property bool $chat_is_bold
 * @property bool $chat_is_italic
 * @property string|null $name_tag
 * @property bool $show_monster_to_low_level_message
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Game\Messages\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAutoDisenchant($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAutoDisenchantAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBannedReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCanSpeakAgainAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereChatIsBold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereChatIsItalic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereChatTextColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGuideEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIgnoredUnbanRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsSilenced($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereKingdomAttackEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastLoggedIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMessageThrottleCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNameTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRebuiltBuildingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereShowBuildingRebuiltMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereShowBuildingUpgradeMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereShowKingdomUpdateMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereShowMonsterToLowLevelMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereShowUnitRecruitmentMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTimeoutUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUnBanRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUnbannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpgradedBuildingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereWillBeDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int|null $amount_signed_in
 * @property int|null $amount_registered
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array|null $invalid_ips
 * @property array|null $invalid_user_ids
 * @method static \Database\Factories\UserSiteAccessStatisticsFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics whereAmountRegistered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics whereAmountSignedIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics whereInvalidIps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics whereInvalidUserIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSiteAccessStatistics whereUpdatedAt($value)
 */
	class UserSiteAccessStatistics extends \Eloquent {}
}

namespace App\Flare\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int $monster_id
 * @property int $character_deaths
 * @property bool $monster_was_killed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Flare\Models\Character|null $character
 * @property-read \App\Flare\Models\Monster|null $monster
 * @method static \Database\Factories\WeeklyMonsterFightFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight query()
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereCharacterDeaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereMonsterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereMonsterWasKilled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WeeklyMonsterFight whereUpdatedAt($value)
 */
	class WeeklyMonsterFight extends \Eloquent {}
}

namespace App\Game\Messages\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $from_user
 * @property int|null $to_user
 * @property string $message
 * @property int|null $x_position
 * @property int|null $y_position
 * @property string|null $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool|null $hide_location
 * @property-read \App\Flare\Models\User|null $fromUser
 * @property-read \App\Flare\Models\User|null $toUser
 * @property-read \App\Flare\Models\User $user
 * @method static \Database\Factories\MessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereFromUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereHideLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereToUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereXPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereYPosition($value)
 */
	class Message extends \Eloquent {}
}

