<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Transformers\CharacterStatDetailsTransformer;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;

class CharacterTopsInspectionService
{
    use BuildsTopsResponses;

    public function __construct(
        private readonly FactionLoyaltyTopsService $factionLoyaltyTopsService,
        private readonly KingdomTopsService $kingdomTopsService,
    ) {}

    public function overview(Character $character): array
    {
        $character->load(['race', 'class', 'map.gameMap', 'kingdoms']);
        $lastActivity = UserLoginDuration::where('user_id', $character->user_id)
            ->orderByRaw('COALESCE(last_activity, logged_in_at) desc')
            ->first();

        return [
            'id' => $character->id,
            'name' => $character->name,
            'level' => $character->level,
            'xp' => $character->xp,
            'xp_next' => $character->xp_next,
            'race' => $character->race?->name,
            'class' => $character->class?->name,
            'current_map' => $character->map?->gameMap?->name,
            'online' => $character->isLoggedIn(),
            'last_active_at' => $lastActivity?->last_activity?->toISOString() ?? $lastActivity?->logged_in_at?->toISOString(),
            'gold' => $character->gold,
            'shards' => $character->shards,
            'copper_coins' => $character->copper_coins,
            'gold_dust' => $character->gold_dust,
            'inventory_count' => $character->getInventoryCount(),
            'inventory_max' => $character->inventory_max,
            'gem_bag_count' => $character->getGemBagCount(),
            'alchemy_bag_count' => $character->getAlchemyBagCount(),
            'damage_stat' => $character->damage_stat,
            'to_hit_stat' => $character->class?->to_hit_stat,
            'automation_status' => [
                'auto_battling' => $character->is_auto_battling,
                'faction_loyalty_running' => $character->isFactionLoyaltyAutomationRunning(),
            ],
            'kingdom_count' => $character->kingdoms->where('npc_owned', false)->count(),
        ];
    }

    public function stats(Character $character): array
    {
        $character->loadMissing([
            'class',
            'classSpecialsEquipped.gameClassSpecial',
            'inventory.slots.item.itemPrefix',
            'inventory.slots.item.itemSuffix',
            'inventory.slots.item.sockets.gem',
            'inventory.slots.item.itemSkill',
            'map.gameMap',
            'skills.baseSkill',
        ]);

        if (is_null($character->inventory)) {
            return [
                'base_stats' => [
                    'str' => $character->str,
                    'dur' => $character->dur,
                    'dex' => $character->dex,
                    'chr' => $character->chr,
                    'int' => $character->int,
                    'agi' => $character->agi,
                    'focus' => $character->focus,
                    'ac' => $character->ac,
                ],
                'modded_stats' => [
                    'str' => $character->str,
                    'dur' => $character->dur,
                    'dex' => $character->dex,
                    'chr' => $character->chr,
                    'int' => $character->int,
                    'agi' => $character->agi,
                    'focus' => $character->focus,
                    'ac' => $character->ac,
                ],
                'damage_stat' => $character->damage_stat,
                'weapon_damage' => null,
                'spell_damage' => null,
                'healing' => null,
                'ac' => $character->ac,
                'to_hit' => $character->{$character->class?->to_hit_stat ?? 'dex'},
                'resistances' => [
                    'spell_evasion' => $character->spell_evasion,
                    'artifact_annulment' => $character->artifact_annulment,
                ],
                'elemental_atonement' => [],
                'stat_breakdown' => [],
            ];
        }

        $statDetails = resolve(CharacterStatDetailsTransformer::class)->transform($character);

        return [
            'base_stats' => [
                'str' => $statDetails['str'],
                'dur' => $statDetails['dur'],
                'dex' => $statDetails['dex'],
                'chr' => $statDetails['chr'],
                'int' => $statDetails['int'],
                'agi' => $statDetails['agi'],
                'focus' => $statDetails['focus'],
                'ac' => $character->ac,
            ],
            'modded_stats' => [
                'str' => $statDetails['str_modded'],
                'dur' => $statDetails['dur_modded'],
                'dex' => $statDetails['dex_modded'],
                'chr' => $statDetails['chr_modded'],
                'int' => $statDetails['int_modded'],
                'agi' => $statDetails['agi_modded'],
                'focus' => $statDetails['focus_modded'],
                'ac' => $statDetails['ac'],
            ],
            'damage_stat' => $character->damage_stat,
            'weapon_damage' => $statDetails['weapon_attack'],
            'spell_damage' => $statDetails['spell_damage'],
            'healing' => $statDetails['healing_amount'],
            'ac' => $statDetails['ac'],
            'to_hit' => $character->{$character->class?->to_hit_stat ?? 'dex'},
            'resistances' => [
                'spell_evasion' => $character->spell_evasion,
                'artifact_annulment' => $character->artifact_annulment,
                'ambush_resistance' => $statDetails['ambush_resistance_chance'],
                'counter_resistance' => $statDetails['counter_resistance_chance'],
                'devouring_light_resistance' => $statDetails['devouring_light_res'],
                'devouring_darkness_resistance' => $statDetails['devouring_darkness_res'],
            ],
            'elemental_atonement' => $character->getInformation()->buildElementalAtonement() ?? [],
            'stat_breakdown' => [],
        ];
    }

    public function equipment(Character $character): array
    {
        $equippedSet = $character->inventorySets()->where('is_equipped', true)->with('slots.item.itemPrefix', 'slots.item.itemSuffix', 'slots.item.sockets.gem', 'slots.item.itemSkill')->first();

        if (! is_null($equippedSet)) {
            return [
                'source' => 'inventory_set',
                'set_name' => $equippedSet->name,
                'items' => $equippedSet->slots->where('equipped', true)->map(fn ($slot) => $this->equipmentItem($slot->position, $slot->item))->values()->all(),
            ];
        }

        $inventory = $character->inventory;

        if (is_null($inventory)) {
            return ['source' => 'inventory', 'set_name' => null, 'items' => []];
        }

        $slots = InventorySlot::where('inventory_id', $inventory->id)
            ->where('equipped', true)
            ->with('item.itemPrefix', 'item.itemSuffix', 'item.sockets.gem', 'item.itemSkill')
            ->get();

        return [
            'source' => 'inventory',
            'set_name' => null,
            'items' => $slots->map(fn ($slot) => $this->equipmentItem($slot->position, $slot->item))->values()->all(),
        ];
    }

    public function skills(Character $character): array
    {
        $character->load(['skills.baseSkill', 'passiveSkills.passiveSkill', 'classRanks.gameClass', 'classSpecialsEquipped.gameClassSpecial']);

        return [
            'regular_skills' => $character->skills->where('is_hidden', false)->map(fn ($skill) => [
                'name' => $skill->baseSkill?->name,
                'level' => $skill->level,
                'xp' => $skill->xp,
                'xp_max' => $skill->xp_max,
                'skill_type' => $skill->skill_type,
            ])->values()->all(),
            'passive_skills' => $character->passiveSkills->map(fn ($skill) => [
                'name' => $skill->passiveSkill?->name,
                'current_level' => $skill->current_level,
            ])->values()->all(),
            'class_ranks' => $character->classRanks->map(fn ($rank) => [
                'class' => $rank->gameClass?->name,
                'current_xp' => $rank->current_xp,
                'required_xp' => $rank->required_xp,
                'level' => $rank->level,
            ])->values()->all(),
            'class_specialties_equipped' => $character->classSpecialsEquipped->map(fn ($special) => [
                'name' => $special->gameClassSpecial?->name,
            ])->values()->all(),
        ];
    }

    public function factions(Character $character): array
    {
        return $this->factionLoyaltyTopsService->detail($character);
    }

    public function reincarnation(Character $character): array
    {
        return [
            'times_reincarnated' => $character->times_reincarnated ?? 0,
            'reincarnated_stat_increase' => $character->reincarnated_stat_increase ?? 0,
            'xp_penalty' => $character->xp_penalty ?? 0,
            'base_stat_mod' => $character->base_stat_mod ?? 0,
            'base_damage_stat_mod' => $character->base_damage_stat_mod ?? 0,
        ];
    }

    public function activity(Character $character): array
    {
        $loginRows = UserLoginDuration::where('user_id', $character->user_id)->get();

        return [
            'last_login_at' => $loginRows->max('logged_in_at')?->toISOString(),
            'last_activity_at' => $loginRows->max('last_activity')?->toISOString(),
            'login_duration_7_days' => $this->loginDuration($loginRows, 7),
            'login_duration_14_days' => $this->loginDuration($loginRows, 14),
            'login_duration_30_days' => $this->loginDuration($loginRows, 30),
            'login_count_7_days' => $this->loginCount($loginRows, 7),
            'login_count_14_days' => $this->loginCount($loginRows, 14),
            'login_count_30_days' => $this->loginCount($loginRows, 30),
            'exploration_run_count' => ExplorationLog::where('character_id', $character->id)->count(),
            'exploration_kills' => ExplorationLog::where('character_id', $character->id)->sum('kills'),
            'delve_run_count' => DelveExploration::where('character_id', $character->id)->count(),
            'delve_outcome_counts' => DelveExploration::where('character_id', $character->id)->pluck('ended_reason')->countBy()->all(),
            'faction_loyalty_automation_count' => FactionLoyaltyAutomation::where('character_id', $character->id)->count(),
            'latest_faction_loyalty_action' => FactionLoyaltyAutomation::where('character_id', $character->id)->latest('last_automation_action_at')->value('last_automation_action'),
            'latest_faction_loyalty_outcome' => FactionLoyaltyAutomation::where('character_id', $character->id)->latest('last_automation_action_at')->value('last_fight_outcome'),
            'quest_completion_count' => QuestsCompleted::where('character_id', $character->id)->whereNotNull('quest_id')->count(),
            'guide_quest_completion_count' => QuestsCompleted::where('character_id', $character->id)->whereNotNull('guide_quest_id')->count(),
        ];
    }

    public function quests(Character $character): array
    {
        $quests = QuestsCompleted::where('character_id', $character->id)->with(['quest', 'guideQuest'])->get();

        return [
            'completed_quest_count' => $quests->whereNotNull('quest_id')->count(),
            'completed_guide_quest_count' => $quests->whereNotNull('guide_quest_id')->count(),
            'completed_quests' => $quests->whereNotNull('quest_id')->map(fn ($quest) => [
                'id' => $quest->quest_id,
                'name' => $quest->quest?->name,
            ])->values()->all(),
            'completed_guide_quests' => $quests->whereNotNull('guide_quest_id')->map(fn ($quest) => [
                'id' => $quest->guide_quest_id,
                'name' => $quest->guideQuest?->name,
            ])->values()->all(),
        ];
    }

    public function kingdoms(Character $character): array
    {
        return $this->kingdomTopsService->detail($character);
    }

    public function analytics(Character $character): array
    {
        return [
            'summary' => [
                'exploration_kills' => ExplorationLog::where('character_id', $character->id)->sum('kills'),
                'exploration_runs' => ExplorationLog::where('character_id', $character->id)->count(),
                'delve_runs' => DelveExploration::where('character_id', $character->id)->count(),
                'quests_completed' => QuestsCompleted::where('character_id', $character->id)->count(),
            ],
            'tables' => [
                'exploration_by_day' => ExplorationLog::where('character_id', $character->id)
                    ->selectRaw('DATE(started_at) as date, SUM(kills) as kills, COUNT(*) as runs')
                    ->groupByRaw('DATE(started_at)')
                    ->orderBy('date')
                    ->get()
                    ->map(fn ($row) => ['date' => $row->date, 'kills' => (int) $row->kills, 'runs' => (int) $row->runs])
                    ->all(),
            ],
        ];
    }

    public function fullProfile(Character $character): array
    {
        return [
            'overview' => $this->overview($character),
            'stats' => $this->stats($character),
            'equipment' => $this->equipment($character),
            'skills' => $this->skills($character),
            'factions' => $this->factions($character),
            'reincarnation' => $this->reincarnation($character),
            'activity' => $this->activity($character),
            'quests' => $this->quests($character),
            'kingdoms' => $this->kingdoms($character),
            'analytics' => $this->analytics($character),
        ];
    }

    private function equipmentItem(?string $position, $item): array
    {
        return [
            'position' => $position,
            'name' => $item?->affix_name,
            'type' => $item?->type,
            'attack' => $item?->base_damage,
            'ac' => $item?->base_ac,
            'stat_modifiers' => [
                'str' => $item?->str_mod,
                'dur' => $item?->dur_mod,
                'dex' => $item?->dex_mod,
                'chr' => $item?->chr_mod,
                'int' => $item?->int_mod,
                'agi' => $item?->agi_mod,
                'focus' => $item?->focus_mod,
            ],
            'prefix' => $item?->itemPrefix?->name,
            'suffix' => $item?->itemSuffix?->name,
            'holy_stacks' => $item?->holy_stacks,
            'attached_gems' => $item?->sockets?->map(fn ($socket) => ['name' => $socket->gem?->name])->values()->all() ?? [],
            'item_skill' => $item?->itemSkill?->name,
        ];
    }

    private function loginDuration($rows, int $days): int
    {
        return (int) $rows->filter(fn ($row) => $row->logged_in_at?->gte(now()->subDays($days)))->sum('duration_in_seconds');
    }

    private function loginCount($rows, int $days): int
    {
        return $rows->filter(fn ($row) => $row->logged_in_at?->gte(now()->subDays($days)))->count();
    }
}
