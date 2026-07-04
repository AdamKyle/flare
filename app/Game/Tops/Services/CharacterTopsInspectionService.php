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
        private readonly CharacterStatDetailsTransformer $characterStatDetailsTransformer,
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

        $statDetails = $this->characterStatDetailsTransformer->transform($character);

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
            'stat_breakdown' => $this->statBreakdown($character, $statDetails),
        ];
    }

    public function equipment(Character $character): array
    {
        $equippedSet = $character->inventorySets()->where('is_equipped', true)->with('slots.item.itemPrefix', 'slots.item.itemSuffix', 'slots.item.sockets.gem', 'slots.item.itemSkill')->first();

        if (! is_null($equippedSet)) {
            return [
                'source' => 'inventory_set',
                'set_name' => $equippedSet->name,
                'items' => $equippedSet->slots->where('equipped', true)->map(fn ($slot) => $this->equipmentItem($slot->position, $slot->item, $slot->id))->values()->all(),
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
            'items' => $slots->map(fn ($slot) => $this->equipmentItem($slot->position, $slot->item, $slot->id))->values()->all(),
        ];
    }

    public function skills(Character $character): array
    {
        $character->load([
            'class',
            'skills.baseSkill',
            'passiveSkills.passiveSkill',
            'classRanks.gameClass',
            'classRanks.weaponMasteries',
            'classSpecialsEquipped.gameClassSpecial.gameClass',
        ]);

        $classRanks = $character->classRanks->filter(fn ($rank): bool => $rank->level > 1)->map(fn ($rank) => [
            'class_id' => $rank->game_class_id,
            'class' => $rank->gameClass?->name,
            'class_name' => $rank->gameClass?->name,
            'game_class_id' => $rank->game_class_id,
            'current_xp' => $rank->current_xp,
            'required_xp' => $rank->required_xp,
            'level' => $rank->level,
            'is_active' => $character->game_class_id === $rank->game_class_id,
            'is_locked' => false,
            'base_class_info' => [
                'name' => $rank->gameClass?->name,
                'description' => $rank->gameClass?->description,
            ],
            'requirements' => [
                'required_xp' => $rank->required_xp,
            ],
            'weapon_masteries' => $rank->weaponMasteries->filter(fn ($mastery): bool => $mastery->level > 1)->map(fn ($mastery) => [
                'id' => $mastery->id,
                'name' => ucwords(str_replace('-', ' ', $mastery->weapon_type)),
                'weapon_type' => $mastery->weapon_type,
                'current_xp' => $mastery->current_xp,
                'required_xp' => $mastery->required_xp,
                'level' => $mastery->level,
            ])->values()->all(),
            'equipped_specialties' => $this->classSpecialtiesForClass($character, $rank->game_class_id, true),
            'unlocked_specialties' => $this->classSpecialtiesForClass($character, $rank->game_class_id, false),
        ])->values()->all();

        $meaningfulSkills = $character->skills->filter(fn ($skill): bool => $skill->level > 1 || $skill->xp > 0);
        $craftingSkills = $meaningfulSkills->filter(fn ($skill): bool => $this->isCraftingSkillName($skill->baseSkill?->name));
        $regularSkills = $meaningfulSkills->reject(fn ($skill): bool => $this->isCraftingSkillName($skill->baseSkill?->name));

        return [
            'regular_skills' => $regularSkills->map(fn ($skill) => [
                'id' => $skill->id,
                'name' => $skill->baseSkill?->name,
                'level' => $skill->level,
                'xp' => $skill->xp,
                'xp_max' => $skill->xp_max,
                'skill_type' => $skill->skill_type,
            ])->values()->all(),
            'passive_skills' => $character->passiveSkills->filter(fn ($skill): bool => $skill->current_level > 1)->map(fn ($skill) => [
                'id' => $skill->passive_skill_id,
                'name' => $skill->passiveSkill?->name,
                'description' => $skill->passiveSkill?->description,
                'current_level' => $skill->current_level,
                'max_level' => $skill->passiveSkill?->max_level,
            ])->values()->all(),
            'class_ranks' => $classRanks,
            'class_specialties_equipped' => $character->classSpecialsEquipped->map(fn ($special) => [
                'id' => $special->game_class_special_id,
                'name' => $special->gameClassSpecial?->name,
                'description' => $special->gameClassSpecial?->description,
                'level' => $special->level ?? null,
                'current_xp' => $special->current_xp,
                'required_xp' => $special->required_xp,
                'equipped' => $special->equipped,
                'class_name' => $special->gameClassSpecial?->gameClass?->name,
            ])->values()->all(),
            'crafting_skills' => $craftingSkills->map(fn ($skill) => [
                'id' => $skill->id,
                'name' => $skill->baseSkill?->name,
                'level' => $skill->level,
                'xp' => $skill->xp,
                'xp_max' => $skill->xp_max,
                'skill_type' => $skill->skill_type,
            ])->values()->all(),
            'kingdom_passives' => $character->passiveSkills->filter(fn ($skill): bool => $skill->current_level > 1)->map(fn ($skill) => [
                'id' => $skill->passive_skill_id,
                'name' => $skill->passiveSkill?->name,
                'description' => $skill->passiveSkill?->description,
                'current_level' => $skill->current_level,
                'max_level' => $skill->passiveSkill?->max_level,
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
        $quests = QuestsCompleted::where('character_id', $character->id)->with(['quest.rewardItem', 'quest.requiredPlane', 'quest.factionMap', 'guideQuest'])->get();

        return [
            'completed_quest_count' => $quests->whereNotNull('quest_id')->count(),
            'completed_guide_quest_count' => $quests->whereNotNull('guide_quest_id')->count(),
            'completed_quests' => $quests->whereNotNull('quest_id')->map(fn ($quest) => $this->questDetails($quest))->values()->all(),
            'completed_guide_quests' => $quests->whereNotNull('guide_quest_id')->map(fn ($quest) => $this->guideQuestDetails($quest))->values()->all(),
            'completion_chart' => $this->questCompletionChart($quests),
        ];
    }

    public function kingdoms(Character $character): array
    {
        $kingdoms = $this->kingdomTopsService->detail($character);
        $resourceTotals = $kingdoms['resource_totals'] ?? [];

        unset($kingdoms['map_distribution']);

        $kingdoms['kingdom_summary_chart'] = $this->chartPayload('Kingdom Summary', 'count', [
            ['label' => 'Kingdoms', 'value' => $kingdoms['kingdom_count'] ?? 0],
            ['label' => 'Capitals', 'value' => $kingdoms['capital_count'] ?? 0],
            ['label' => 'Treasury', 'value' => $kingdoms['total_treasury'] ?? 0],
            ['label' => 'Gold Bars', 'value' => $kingdoms['total_gold_bars'] ?? 0],
            ['label' => 'Population', 'value' => $kingdoms['population_total'] ?? 0],
            ['label' => 'Morale', 'value' => $kingdoms['morale_average'] ?? 0],
        ]);
        $kingdoms['resource_totals_chart'] = $this->chartPayload('Resource Totals', 'resources', [
            ['label' => 'Stone', 'value' => $resourceTotals['stone'] ?? 0],
            ['label' => 'Wood', 'value' => $resourceTotals['wood'] ?? 0],
            ['label' => 'Clay', 'value' => $resourceTotals['clay'] ?? 0],
            ['label' => 'Iron', 'value' => $resourceTotals['iron'] ?? 0],
            ['label' => 'Steel', 'value' => $resourceTotals['steel'] ?? 0],
        ]);
        $kingdoms['top_kingdoms_chart'] = [
            'source' => 'kingdoms',
            'unit' => 'value',
            'series' => collect($kingdoms['kingdoms'] ?? [])->map(fn (array $kingdom): array => [
                'label' => $kingdom['name'] ?? 'Kingdom',
                'points' => [
                    ['label' => 'Treasury', 'value' => (int) ($kingdom['treasury'] ?? 0)],
                    ['label' => 'Gold Bars', 'value' => (int) ($kingdom['gold_bars'] ?? 0)],
                    ['label' => 'Population', 'value' => (int) ($kingdom['current_population'] ?? 0)],
                    ['label' => 'Morale', 'value' => (int) ($kingdom['current_morale'] ?? 0)],
                ],
            ])->values()->all(),
        ];

        return $kingdoms;
    }

    public function analytics(Character $character): array
    {
        $summary = [
            'exploration_kills' => ExplorationLog::where('character_id', $character->id)->sum('kills'),
            'exploration_runs' => ExplorationLog::where('character_id', $character->id)->count(),
            'delve_runs' => DelveExploration::where('character_id', $character->id)->count(),
            'quests_completed' => QuestsCompleted::where('character_id', $character->id)->count(),
        ];

        return [
            'summary' => [
                ...$summary,
            ],
            'analytics_summary_chart' => $this->chartPayload('Analytics Summary', 'count', [
                ['label' => 'Exploration Kills', 'value' => $summary['exploration_kills']],
                ['label' => 'Exploration Runs', 'value' => $summary['exploration_runs']],
                ['label' => 'Delve Runs', 'value' => $summary['delve_runs']],
                ['label' => 'Quests Completed', 'value' => $summary['quests_completed']],
            ]),
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
        $overview = $this->overview($character);
        $stats = $this->stats($character);
        $skills = $this->skills($character);
        $reincarnation = $this->reincarnation($character);

        return [
            'overview' => $overview,
            'summary' => $this->summary($overview, $stats, $reincarnation),
            'info' => $this->info($overview, $stats),
            'stats' => $stats,
            'additional_stats' => $this->additionalStats($stats, $skills, $reincarnation),
            'equipment' => $this->equipment($character),
            'skills' => $skills,
            'crafting_skills' => $skills['crafting_skills'],
            'kingdom_passives' => $skills['kingdom_passives'],
            'class_ranks' => $skills['class_ranks'],
            'factions' => $this->factions($character),
            'reincarnation' => $reincarnation,
            'activity' => $this->activity($character),
            'quests' => $this->quests($character),
            'kingdoms' => $this->kingdoms($character),
            'analytics' => $this->analytics($character),
        ];
    }

    private function summary(array $overview, array $stats, array $reincarnation): array
    {
        return [
            'gold' => $overview['gold'],
            'gold_dust' => $overview['gold_dust'],
            'shards' => $overview['shards'],
            'copper_coins' => $overview['copper_coins'],
            'inventory_count' => $overview['inventory_count'],
            'inventory_max' => $overview['inventory_max'],
            'alchemy_bag_count' => $overview['alchemy_bag_count'],
            'gem_bag_count' => $overview['gem_bag_count'],
            'damage_stat' => $overview['damage_stat'],
            'to_hit' => $stats['to_hit'],
            'class_bonus' => $overview['class'] ?? null,
            'fight_timeout' => null,
            'movement_timeout' => null,
            'reincarnation' => $reincarnation,
        ];
    }

    private function info(array $overview, array $stats): array
    {
        return [
            'name' => $overview['name'],
            'race' => $overview['race'],
            'class' => $overview['class'],
            'level' => $overview['level'],
            'max_health' => $stats['modded_stats']['dur'] ?? null,
            'total_attack' => $stats['weapon_damage'],
            'heal_for' => $stats['healing'],
            'ac' => $stats['ac'],
            'xp' => $overview['xp'],
            'xp_next' => $overview['xp_next'],
        ];
    }

    private function additionalStats(array $stats, array $skills, array $reincarnation): array
    {
        return [
            'character_stats' => $stats,
            'class_ranks' => $skills['class_ranks'],
            'resistances' => $stats['resistances'],
            'elemental_atonement' => $stats['elemental_atonement'],
            'reincarnation' => $reincarnation,
        ];
    }

    private function equipmentItem(?string $position, $item, ?int $slotId = null): array
    {
        $attachedAffixesCount = (int) (! is_null($item?->itemPrefix)) + (int) (! is_null($item?->itemSuffix));
        $socketAmount = (int) ($item?->socket_count ?? $item?->sockets?->count() ?? 0);

        return [
            'position' => $position,
            'id' => $item?->id,
            'item_id' => $item?->id,
            'slot_id' => $slotId,
            'item_name' => $item?->affix_name,
            'name' => $item?->affix_name,
            'type' => $item?->type,
            'description' => $item?->description,
            'is_unique' => (bool) $item?->is_unique,
            'is_mythic' => (bool) $item?->is_mythic,
            'is_cosmic' => (bool) $item?->is_cosmic,
            'attached_affixes_count' => $attachedAffixesCount,
            'affix_count' => $attachedAffixesCount,
            'has_holy_stacks_applied' => (int) ($item?->holy_stacks ?? 0) > 0,
            'attack' => $item?->base_damage,
            'healing' => $item?->base_healing,
            'ac' => $item?->base_ac,
            'item_atonements' => [
                'atonements' => [],
                'elemental_damage' => [
                    'name' => 'None',
                    'amount' => 0,
                ],
            ],
            'str_modifier' => $item?->str_mod ?? 0,
            'dex_modifier' => $item?->dex_mod ?? 0,
            'agi_modifier' => $item?->agi_mod ?? 0,
            'chr_modifier' => $item?->chr_mod ?? 0,
            'dur_modifier' => $item?->dur_mod ?? 0,
            'int_modifier' => $item?->int_mod ?? 0,
            'focus_modifier' => $item?->focus_mod ?? 0,
            'base_damage' => $item?->base_damage ?? 0,
            'base_ac' => $item?->base_ac ?? 0,
            'base_healing' => $item?->base_healing ?? 0,
            'base_damage_mod' => $item?->base_damage_mod ?? 0,
            'base_ac_mod' => $item?->base_ac_mod ?? 0,
            'base_healing_mod' => $item?->base_healing_mod ?? 0,
            'skill_name' => $item?->skill_name,
            'skill_bonus' => $item?->skill_bonus ?? 0,
            'skill_training_bonus' => $item?->skill_training_bonus ?? 0,
            'spell_evasion' => $item?->spell_evasion ?? 0,
            'healing_reduction' => $item?->healing_reduction ?? 0,
            'affix_damage_reduction' => $item?->affix_damage_reduction ?? 0,
            'devouring_light' => $item?->devouring_light ?? 0,
            'devouring_darkness' => $item?->devouring_darkness ?? 0,
            'ambush_chance' => $item?->ambush_chance ?? 0,
            'ambush_resistance' => $item?->ambush_resistance ?? 0,
            'counter_chance' => $item?->counter_chance ?? 0,
            'counter_resistance' => $item?->counter_resistance ?? 0,
            'socket_amount' => $socketAmount,
            'holy_stack_count' => $item?->holy_stacks ?? 0,
            'holy_stacks_applied' => $item?->holy_stacks ?? 0,
            'holy_stack_stat_bonus' => 0,
            'resurrection_chance' => $item?->resurrection_chance ?? 0,
            'applied_stacks' => [],
            'gem_slots' => $socketAmount,
            'stat_modifiers' => [
                'str' => $item?->str_mod,
                'dur' => $item?->dur_mod,
                'dex' => $item?->dex_mod,
                'chr' => $item?->chr_mod,
                'int' => $item?->int_mod,
                'agi' => $item?->agi_mod,
                'focus' => $item?->focus_mod,
            ],
            'prefix' => $item?->itemPrefix ? [
                'name' => $item->itemPrefix->name,
                'description' => $item->itemPrefix->description,
            ] : null,
            'item_prefix' => $this->affixDetails($item?->itemPrefix),
            'suffix' => $item?->itemSuffix ? [
                'name' => $item->itemSuffix->name,
                'description' => $item->itemSuffix->description,
            ] : null,
            'item_suffix' => $this->affixDetails($item?->itemSuffix),
            'holy_stacks' => $item?->holy_stacks,
            'sockets' => $item?->sockets?->map(fn ($socket) => [
                'gem_name' => $socket->gem?->name,
                'gem_type' => $socket->gem?->gem_type,
                'tier' => $socket->gem?->tier,
            ])->values()->all() ?? [],
            'attached_gems' => $item?->sockets?->map(fn ($socket) => ['name' => $socket->gem?->name])->values()->all() ?? [],
            'item_skill' => $item?->itemSkill ? [
                'name' => $item->itemSkill->name,
                'description' => $item->itemSkill->description,
            ] : null,
            'item_skills' => $item?->itemSkill ? [[
                'name' => $item->itemSkill->name,
                'description' => $item->itemSkill->description,
            ]] : [],
            'item_skill_progressions' => [],
            'usable' => (bool) $item?->usable,
        ];
    }

    private function affixDetails($affix): ?array
    {
        if (is_null($affix)) {
            return null;
        }

        return [
            'id' => $affix->id,
            'name' => $affix->name,
            'description' => $affix->description,
            'str_mod' => $affix->str_mod ?? 0,
            'dex_mod' => $affix->dex_mod ?? 0,
            'agi_mod' => $affix->agi_mod ?? 0,
            'chr_mod' => $affix->chr_mod ?? 0,
            'dur_mod' => $affix->dur_mod ?? 0,
            'int_mod' => $affix->int_mod ?? 0,
            'focus_mod' => $affix->focus_mod ?? 0,
        ];
    }

    private function questDetails(QuestsCompleted $questCompletion): array
    {
        $quest = $questCompletion->quest;

        return [
            'id' => $questCompletion->quest_id,
            'name' => $quest?->name,
            'before_completion_description' => $quest?->before_completion_description,
            'after_completion_description' => $quest?->after_completion_description,
            'reincarnated_times' => $quest?->reincarnated_times,
            'required_faction_level' => $quest?->required_faction_level,
            'required_fame_level' => $quest?->required_fame_level,
            'access_to_map_id' => $quest?->access_to_map_id,
            'plane' => $quest?->requiredPlane?->name,
            'faction_plane' => $quest?->factionMap?->name,
            'only_for_event' => $quest?->only_for_event,
            'rewards' => [
                'gold' => $quest?->reward_gold ?? 0,
                'gold_dust' => $quest?->reward_gold_dust ?? 0,
                'shards' => $quest?->reward_shards ?? 0,
                'copper_coins' => 0,
                'xp' => $quest?->reward_xp ?? 0,
                'item_name' => $quest?->rewardItem?->affix_name,
            ],
            'completed_at' => $questCompletion->created_at?->toISOString(),
        ];
    }

    private function guideQuestDetails(QuestsCompleted $questCompletion): array
    {
        $quest = $questCompletion->guideQuest;

        return [
            'id' => $questCompletion->guide_quest_id,
            'name' => $quest?->name,
            'intro_text' => $quest?->intro_text,
            'instructions' => $quest?->instructions,
            'desktop_instructions' => $quest?->desktop_instructions,
            'mobile_instructions' => $quest?->mobile_instructions,
            'requirements' => [
                'required_level' => $quest?->required_level,
                'required_reincarnation_amount' => $quest?->required_reincarnation_amount,
                'required_skill_level' => $quest?->required_skill_level,
                'required_faction_level' => $quest?->required_faction_level,
                'required_kingdoms' => $quest?->required_kingdoms,
                'required_class_rank_level' => $quest?->required_class_rank_level,
                'required_fame_level' => $quest?->required_fame_level,
            ],
            'rewards' => [
                'gold' => $quest?->gold_reward ?? 0,
                'gold_dust' => $quest?->gold_dust_reward ?? 0,
                'shards' => $quest?->shards_reward ?? 0,
                'xp' => $quest?->xp_reward ?? 0,
            ],
            'completed_at' => $questCompletion->created_at?->toISOString(),
        ];
    }

    private function questCompletionChart($quests): array
    {
        $datedQuests = $quests->filter(fn (QuestsCompleted $quest): bool => ! is_null($quest->created_at));

        if ($datedQuests->isEmpty()) {
            return [
                'source' => 'quests_completed.created_at',
                'unit' => 'completions',
                'granularity' => null,
                'series' => [
                    ['label' => 'Quests', 'points' => []],
                    ['label' => 'Guide Quests', 'points' => []],
                ],
            ];
        }

        $oldest = $datedQuests->min('created_at');
        $newest = $datedQuests->max('created_at');
        $rangeDays = max(1, $oldest->diffInDays($newest) + 1);
        $granularity = match (true) {
            $oldest->diffInHours($newest) <= 48 => 'hours',
            $rangeDays <= 90 => 'days',
            $rangeDays <= 365 => 'weeks',
            $rangeDays <= 1095 => 'months',
            default => 'years',
        };

        return [
            'source' => 'quests_completed.created_at',
            'unit' => 'completions',
            'granularity' => $granularity,
            'series' => [
                [
                    'label' => 'Quests',
                    'points' => $this->questCompletionPoints($datedQuests->whereNotNull('quest_id'), $granularity),
                ],
                [
                    'label' => 'Guide Quests',
                    'points' => $this->questCompletionPoints($datedQuests->whereNotNull('guide_quest_id'), $granularity),
                ],
            ],
        ];
    }

    private function questCompletionPoints($quests, string $granularity): array
    {
        return $quests
            ->groupBy(fn (QuestsCompleted $quest): string => match ($granularity) {
                'hours' => $quest->created_at->copy()->startOfHour()->toISOString(),
                'days' => $quest->created_at->copy()->startOfDay()->toISOString(),
                'weeks' => $quest->created_at->copy()->startOfWeek()->toISOString(),
                'months' => $quest->created_at->copy()->startOfMonth()->toISOString(),
                default => $quest->created_at->copy()->startOfYear()->toISOString(),
            })
            ->map(fn ($rows, string $date): array => [
                'label' => match ($granularity) {
                    'hours' => $rows->first()->created_at->copy()->startOfHour()->format('Y-m-d H:00'),
                    'days' => $rows->first()->created_at->format('Y-m-d'),
                    'weeks' => $rows->first()->created_at->copy()->startOfWeek()->format('Y-m-d'),
                    'months' => $rows->first()->created_at->format('Y-m'),
                    default => $rows->first()->created_at->format('Y'),
                },
                'date' => $date,
                'value' => $rows->count(),
            ])
            ->values()
            ->all();
    }

    private function chartPayload(string $source, string $unit, array $points): array
    {
        return [
            'source' => $source,
            'unit' => $unit,
            'points' => collect($points)->map(fn (array $point): array => [
                'label' => $point['label'],
                'value' => (int) $point['value'],
            ])->values()->all(),
        ];
    }

    private function isCraftingSkillName(?string $name): bool
    {
        if (is_null($name)) {
            return false;
        }

        return in_array(strtolower($name), [
            'weapon crafting',
            'armour crafting',
            'armor crafting',
            'ring crafting',
            'spell crafting',
            'enchanting',
            'disenchanting',
            'trinketry',
            'gem crafting',
            'alchemy',
        ], true);
    }

    private function loginDuration($rows, int $days): int
    {
        return (int) $rows->filter(fn ($row) => $row->logged_in_at?->gte(now()->subDays($days)))->sum('duration_in_seconds');
    }

    private function loginCount($rows, int $days): int
    {
        return $rows->filter(fn ($row) => $row->logged_in_at?->gte(now()->subDays($days)))->count();
    }

    private function statBreakdown(Character $character, array $statDetails): array
    {
        return [
            'damage_stat' => [
                'label' => 'Damage Stat',
                'value' => $character->damage_stat,
                'description' => 'Primary stat used by this character for damage calculations.',
            ],
            'weapon_damage' => [
                'label' => 'Weapon Damage',
                'value' => $statDetails['weapon_attack'] ?? null,
                'description' => 'Read-only weapon damage total from the public inspect payload.',
            ],
            'spell_damage' => [
                'label' => 'Spell Damage',
                'value' => $statDetails['spell_damage'] ?? null,
                'description' => 'Read-only spell damage total from the public inspect payload.',
            ],
            'healing' => [
                'label' => 'Healing',
                'value' => $statDetails['healing_amount'] ?? null,
                'description' => 'Read-only healing total from the public inspect payload.',
            ],
            'ac' => [
                'label' => 'AC',
                'value' => $statDetails['ac'] ?? null,
                'description' => 'Read-only armor class total from the public inspect payload.',
            ],
            'to_hit' => [
                'label' => 'To-Hit',
                'value' => $character->{$character->class?->to_hit_stat ?? 'dex'},
                'description' => 'Current public to-hit stat value for this character.',
            ],
        ];
    }

    private function classSpecialtiesForClass(Character $character, int $gameClassId, bool $equipped): array
    {
        return $character->classSpecialsEquipped
            ->filter(function ($special) use ($gameClassId, $equipped): bool {
                return $special->equipped === $equipped
                    && $special->level > 1
                    && $special->gameClassSpecial?->game_class_id === $gameClassId;
            })
            ->map(fn ($special) => [
                'id' => $special->game_class_special_id,
                'name' => $special->gameClassSpecial?->name,
                'description' => $special->gameClassSpecial?->description,
                'level' => $special->level,
                'current_xp' => $special->current_xp,
                'required_xp' => $special->required_xp,
                'equipped' => $special->equipped,
                'class_name' => $special->gameClassSpecial?->gameClass?->name,
                'requires_class_rank_level' => $special->gameClassSpecial?->requires_class_rank_level,
                'specialty_damage' => $special->gameClassSpecial?->specialty_damage,
                'attack_type_required' => $special->gameClassSpecial?->attack_type_required,
            ])
            ->values()
            ->all();
    }
}
