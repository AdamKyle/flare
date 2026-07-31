<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\GameClassSpecial;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\CharacterElementalAtonementTransformer;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Flare\Transformers\CharacterReincarnationInfoTransformer;
use App\Flare\Transformers\CharacterResistanceInfoTransformer;
use App\Flare\Transformers\CharacterStatDetailsTransformer;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;
use Carbon\Carbon;

class CharacterTopsInspectionService
{
    use BuildsTopsResponses;

    public function __construct(
        private readonly FactionLoyaltyTopsService $factionLoyaltyTopsService,
        private readonly KingdomTopsService $kingdomTopsService,
        private readonly CharacterStatDetailsTransformer $characterStatDetailsTransformer,
        private readonly BasicSkillsTransformer $basicSkillsTransformer,
        private readonly ItemTransformer $itemTransformer,
        private readonly GuideQuestService $guideQuestService,
        private readonly CharacterPassiveSkills $characterPassiveSkills,
        private readonly SkillsTransformer $skillsTransformer,
        private readonly CharacterGemsTransformer $characterGemsTransformer,
        private readonly CharacterResistanceInfoTransformer $characterResistanceInfoTransformer,
        private readonly CharacterElementalAtonementTransformer $characterElementalAtonementTransformer,
        private readonly CharacterReincarnationInfoTransformer $characterReincarnationInfoTransformer,
        private readonly CharacterStatBuilder $characterStatBuilder,
        private readonly ClassRankService $classRankService,
        private readonly StatModifierDetails $statModifierDetails,
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
                'elemental_atonement' => null,
                'stat_breakdown' => [],
                'stat_details' => null,
                'resistance_info' => null,
                'resurrection_chance' => 0.0,
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
            'elemental_atonement' => $this->characterElementalAtonementTransformer->transform($character)['elemental_atonement'] ?? [],
            'stat_breakdown' => $this->statBreakdownSummary($character, $statDetails),
            'stat_details' => $statDetails,
            'resistance_info' => $this->characterResistanceInfoTransformer->transform($character),
            'resurrection_chance' => $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance(),
        ];
    }

    public function equipment(Character $character): array
    {
        $equippedSet = $character->inventorySets()->where('is_equipped', true)->with(
            'slots.item.itemPrefix',
            'slots.item.itemSuffix',
            'slots.item.sockets.gem',
            'slots.item.itemSkill.children',
            'slots.item.itemSkillProgressions.itemSkill',
        )->first();

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
            ->with(
                'item.itemPrefix',
                'item.itemSuffix',
                'item.sockets.gem',
                'item.itemSkill.children',
                'item.itemSkillProgressions.itemSkill',
            )
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

        $character->loadMissing('inventory');

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
            'game_class' => [
                'id' => $rank->gameClass?->id,
                'name' => $rank->gameClass?->name,
                'to_hit_stat' => $rank->gameClass?->to_hit_stat,
                'accuracy_mod' => $rank->gameClass?->accuracy_mod,
                'looting_mod' => $rank->gameClass?->looting_mod,
            ],
            'requirements' => [
                'required_xp' => $rank->required_xp,
            ],
            'weapon_masteries' => $rank->weaponMasteries->filter(fn ($mastery): bool => $mastery->level > 1)->map(fn ($mastery) => [
                'id' => $mastery->id,
                'name' => ucwords(str_replace('-', ' ', $mastery->weapon_type)),
                'mastery_name' => ucwords(str_replace('-', ' ', $mastery->weapon_type)),
                'weapon_type' => $mastery->weapon_type,
                'current_xp' => $mastery->current_xp,
                'required_xp' => $mastery->required_xp,
                'level' => $mastery->level,
            ])->values()->all(),
        ])->values()->all();

        $meaningfulSkills = $character->skills->filter(fn ($skill): bool => $skill->level > 1 || $skill->xp > 0);
        $regularSkills = $meaningfulSkills->filter(fn ($skill): bool => (bool) $skill->baseSkill?->can_train);
        $craftingSkills = $meaningfulSkills->reject(fn ($skill): bool => (bool) $skill->baseSkill?->can_train);

        return [
            'regular_skills' => $regularSkills->map(fn ($skill) => $this->publicSkill($skill))->values()->all(),
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
            'crafting_skills' => $craftingSkills->map(fn ($skill) => $this->publicSkill($skill))->values()->all(),
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character)
                ->map(fn ($passive): array => $this->publicPassiveTree($passive))
                ->values()
                ->all(),
            'class_ranks_offered' => $this->classRanksOffered($character),
            'class_rank_specialties' => is_null($character->inventory) ? [
                'class_specialties' => GameClassSpecial::all(),
                'specials_equipped' => [],
                'class_ranks' => $character->classRanks->toArray(),
                'other_class_specials' => [],
            ] : $this->classRankService->getSpecials($character),
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
            'reincarnation_details' => $this->characterReincarnationInfoTransformer->transform($character),
        ];
    }

    public function activity(Character $character): array
    {
        $loginRows = UserLoginDuration::where('user_id', $character->user_id)->whereNotNull('logged_in_at')->get();

        return [
            'login_count_chart' => $this->loginCountChart($character, $loginRows),
            'login_duration_chart' => $this->loginDurationChart($character, $loginRows),
        ];
    }

    public function quests(Character $character, ?User $viewer = null): array
    {
        $quests = QuestsCompleted::where('character_id', $character->id)->with([
            'quest.rewardItem',
            'quest.item',
            'quest.item.dropLocation',
            'quest.requiredQuest',
            'quest.requiredQuest.raid',
            'quest.factionMap',
            'quest.secondaryItem',
            'quest.secondaryItem.dropLocation',
            'quest.requiredPlane',
            'quest.factionLoyaltyNpc',
            'quest.factionLoyaltyNpc.gameMap',
            'quest.npc',
            'quest.npc.gameMap',
            'quest.raid',
            'guideQuest',
        ])->get();

        $viewerCharacter = $viewer?->character;

        $viewerCompletedQuestIds = is_null($viewerCharacter) ? [] : QuestsCompleted::where('character_id', $viewerCharacter->id)->whereNotNull('quest_id')->pluck('quest_id')->all();

        $viewerCompletedGuideQuestIds = is_null($viewerCharacter) ? [] : QuestsCompleted::where('character_id', $viewerCharacter->id)->whereNotNull('guide_quest_id')->pluck('guide_quest_id')->all();

        return [
            'completed_quest_count' => $quests->whereNotNull('quest_id')->count(),
            'completed_guide_quest_count' => $quests->whereNotNull('guide_quest_id')->count(),
            'completed_quests' => $quests->whereNotNull('quest_id')->map(fn ($quest) => $this->questDetails($quest, $viewerCharacter, $viewerCompletedQuestIds))->values()->all(),
            'completed_guide_quests' => $quests->whereNotNull('guide_quest_id')->map(fn ($quest) => $this->guideQuestDetails($quest, $viewerCharacter, $viewerCompletedGuideQuestIds))->values()->all(),
            'completion_chart' => $this->questCompletionChart($quests),
            'summary_chart' => $this->questSummaryChart($character, $quests),
        ];
    }

    public function kingdoms(Character $character): array
    {
        $kingdoms = $this->kingdomTopsService->detail($character);

        unset($kingdoms['map_distribution']);

        return $kingdoms;
    }

    public function analytics(Character $character): array
    {
        $explorationLogs = ExplorationLog::where('character_id', $character->id)->whereNotNull('started_at')->get(['started_at', 'kills']);
        $delveExplorations = DelveExploration::where('character_id', $character->id)->whereNotNull('started_at')->get(['started_at']);
        $questsCompleted = QuestsCompleted::where('character_id', $character->id)->whereNotNull('created_at')->get(['created_at']);

        $allDates = $explorationLogs->pluck('started_at')->merge($delveExplorations->pluck('started_at'))->merge($questsCompleted->pluck('created_at'));

        if ($allDates->isEmpty()) {
            return [
                'analytics_kills_chart' => $this->emptyChart('exploration_logs.started_at', 'kills', ['Exploration Kills']),
                'analytics_runs_chart' => $this->emptyChart('exploration_logs.started_at,delve_explorations.started_at,quests_completed.created_at', 'count', ['Exploration Runs', 'Delve Runs', 'Quests Completed']),
            ];
        }

        $granularity = $this->chartGranularity($allDates->min(), $allDates->max());

        return [
            'analytics_kills_chart' => [
                'source' => 'exploration_logs.started_at',
                'unit' => 'kills',
                'granularity' => $granularity,
                'series' => [
                    ['label' => 'Exploration Kills', 'points' => $this->cumulativeSumPoints($explorationLogs, 'started_at', 'kills', $granularity)],
                ],
            ],
            'analytics_runs_chart' => [
                'source' => 'exploration_logs.started_at,delve_explorations.started_at,quests_completed.created_at',
                'unit' => 'count',
                'granularity' => $granularity,
                'series' => [
                    ['label' => 'Exploration Runs', 'points' => $this->cumulativeCountPoints($explorationLogs, 'started_at', $granularity)],
                    ['label' => 'Delve Runs', 'points' => $this->cumulativeCountPoints($delveExplorations, 'started_at', $granularity)],
                    ['label' => 'Quests Completed', 'points' => $this->cumulativeCountPoints($questsCompleted, 'created_at', $granularity)],
                ],
            ],
        ];
    }

    public function statBreakDown(Character $character, string $statType): array
    {
        return $this->statModifierDetails->setCharacter($character)->forStat($statType);
    }

    public function specificStatBreakDown(Character $character, string $type, bool $isVoided): array
    {
        return $this->statModifierDetails->setCharacter($character)->buildSpecificBreakDown($type, $isVoided);
    }

    public function classRanksOffered(Character $character): array
    {
        $character->loadMissing(['classRanks.gameClass', 'classRanks.weaponMasteries', 'classSpecialsEquipped.gameClassSpecial', 'skills.baseSkill']);

        $classIds = $character->classRanks->pluck('game_class_id')->unique()->values()->all();

        $gameSkillsByClass = GameSkill::whereIn('game_class_id', $classIds)->get()->groupBy('game_class_id');
        $gameClassSpecialsByClass = GameClassSpecial::whereIn('game_class_id', $classIds)->get()->groupBy('game_class_id');

        return $character->classRanks->map(function ($rank) use ($character, $gameSkillsByClass, $gameClassSpecialsByClass): array {
            $classId = $rank->game_class_id;

            $unlockedSpecialtyIds = $character->classSpecialsEquipped
                ->filter(fn ($special): bool => $special->level > 1 && $special->gameClassSpecial?->game_class_id === $classId)
                ->pluck('game_class_special_id')
                ->all();
            $leveledSkillIds = $character->skills
                ->filter(fn ($skill): bool => $skill->level > 1 && $skill->baseSkill?->game_class_id === $classId)
                ->pluck('game_skill_id')
                ->all();

            $offeredGameSkills = ($gameSkillsByClass->get($classId) ?? collect())
                ->whereNotIn('id', $leveledSkillIds)
                ->map(fn (GameSkill $gameSkill): array => [
                    'id' => $gameSkill->id,
                    'name' => $gameSkill->name,
                    'description' => $gameSkill->description,
                    'max_level' => $gameSkill->max_level,
                ])->values()->all();

            $remainingSpecialties = ($gameClassSpecialsByClass->get($classId) ?? collect())
                ->reject(fn (GameClassSpecial $special): bool => in_array($special->id, $unlockedSpecialtyIds, true))
                ->map(fn (GameClassSpecial $special): array => [
                    'id' => $special->id,
                    'name' => $special->name,
                    'description' => $special->description,
                    'requires_class_rank_level' => $special->requires_class_rank_level,
                ])->values()->all();

            return [
                'class_id' => $classId,
                'class_name' => $rank->gameClass?->name,
                'offered_game_skills' => $offeredGameSkills,
                'remaining_weapon_masteries' => $rank->weaponMasteries->filter(fn ($mastery): bool => $mastery->level <= 1)->map(fn ($mastery): array => [
                    'id' => $mastery->id,
                    'name' => ucwords(str_replace('-', ' ', $mastery->weapon_type)),
                    'weapon_type' => $mastery->weapon_type,
                    'current_xp' => $mastery->current_xp,
                    'required_xp' => $mastery->required_xp,
                    'level' => $mastery->level,
                ])->values()->all(),
                'remaining_specialties' => $remainingSpecialties,
            ];
        })->values()->all();
    }

    public function fullProfile(Character $character, ?User $viewer = null): array
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
            'class_ranks_offered' => $this->classRanksOffered($character),
            'factions' => $this->factions($character),
            'reincarnation' => $reincarnation,
            'activity' => $this->activity($character),
            'quests' => $this->quests($character, $viewer),
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

    private function equipmentItem(?string $position, ?Item $item, ?int $slotId = null): array
    {
        if (is_null($item)) {
            return [
                'position' => $position,
                'slot_id' => $slotId,
                'item_id' => null,
                'item_name' => null,
            ];
        }

        $transformedItem = $this->itemTransformer->transform($item);
        $transformedItem['sockets'] = $item->sockets
            ->filter(fn ($socket): bool => ! is_null($socket->gem))
            ->map(fn ($socket): array => $this->characterGemsTransformer->transform($socket->gem))
            ->values()
            ->all();

        return [
            ...$transformedItem,
            'position' => $position,
            'slot_id' => $slotId,
            'item_id' => $item->id,
            'item_name' => $item->affix_name,
        ];
    }

    private function publicSkill(Skill $skill): array
    {
        $details = $this->skillsTransformer->transform($skill);
        unset($details['character_id']);

        return [
            ...$this->basicSkillsTransformer->transform($skill),
            'details' => $details,
        ];
    }

    private function questDetails(QuestsCompleted $questCompletion, ?Character $viewerCharacter, array $viewerCompletedQuestIds): array
    {
        $quest = $questCompletion->quest;

        if (is_null($quest)) {
            return [
                'id' => $questCompletion->quest_id,
                'name' => null,
                'completed_at' => $questCompletion->created_at?->toISOString(),
                'inspected_character_completed' => true,
                'parent_quest_id' => null,
                'required_quest_id' => null,
                'required_quest_chain_details' => null,
                'viewer_has_completed' => false,
                'viewer_parent_complete' => false,
                'viewer_required_quest_complete' => false,
                'viewer_required_quest_chain_complete' => false,
                'viewer_completed_quest_ids' => [],
                'details' => null,
            ];
        }

        $chainDetails = $quest->required_quest_chain_details;

        $viewerHasCompleted = ! is_null($viewerCharacter) && in_array($questCompletion->quest_id, $viewerCompletedQuestIds, true);
        $viewerParentComplete = ! is_null($viewerCharacter) && ((bool) $quest->is_parent || in_array($quest->parent_quest_id, $viewerCompletedQuestIds, true));
        $viewerRequiredQuestComplete = ! is_null($viewerCharacter) && (is_null($quest->required_quest_id) || in_array($quest->required_quest_id, $viewerCompletedQuestIds, true));
        $viewerRequiredQuestChainComplete = ! is_null($viewerCharacter) && (is_null($chainDetails) || collect($chainDetails['quest_ids'])->every(fn (int $chainQuestId): bool => in_array($chainQuestId, $viewerCompletedQuestIds, true)));

        return [
            'id' => $questCompletion->quest_id,
            'name' => $quest->name,
            'completed_at' => $questCompletion->created_at?->toISOString(),
            'inspected_character_completed' => true,
            'parent_quest_id' => $quest->parent_quest_id,
            'required_quest_id' => $quest->required_quest_id,
            'required_quest_chain_details' => $chainDetails,
            'viewer_has_completed' => $viewerHasCompleted,
            'viewer_parent_complete' => $viewerParentComplete,
            'viewer_required_quest_complete' => $viewerRequiredQuestComplete,
            'viewer_required_quest_chain_complete' => $viewerRequiredQuestChainComplete,
            'viewer_completed_quest_ids' => is_null($viewerCharacter) ? [] : $viewerCompletedQuestIds,
            'details' => $this->questDetailPayload($quest),
        ];
    }

    private function questDetailPayload(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'before_completion_description' => $quest->before_completion_description,
            'after_completion_description' => $quest->after_completion_description,
            'gold_cost' => $quest->gold_cost,
            'gold_dust_cost' => $quest->gold_dust_cost,
            'shard_cost' => $quest->shard_cost,
            'copper_coin_cost' => $quest->copper_coin_cost,
            'reincarnated_times' => $quest->reincarnated_times,
            'access_to_map_id' => $quest->access_to_map_id,
            'faction_game_map_id' => $quest->faction_game_map_id,
            'required_faction_level' => $quest->required_faction_level,
            'required_quest_id' => $quest->required_quest_id,
            'assisting_npc_id' => $quest->assisting_npc_id,
            'required_fame_level' => $quest->required_fame_level,
            'reward_xp' => $quest->reward_xp,
            'reward_gold' => $quest->reward_gold,
            'reward_gold_dust' => $quest->reward_gold_dust,
            'reward_shards' => $quest->reward_shards,
            'unlocks_skill' => $quest->unlocks_skill,
            'unlocks_skill_name' => $quest->unlocks_skill
                ? SkillTypeValue::tryFrom($quest->unlocks_skill_type)?->getNamedValue()
                : 'N/A',
            'feature_to_unlock_name' => is_null($quest->unlocks_feature)
                ? null
                : $quest->unlocksFeature()?->getNameOfFeature(),
            'unlocks_passive_name' => $quest->unlocks_passive_name,
            'npc' => $this->publicQuestNpc($quest->npc),
            'item' => $this->publicQuestItem($quest->item),
            'secondary_item' => $this->publicQuestItem($quest->secondaryItem),
            'required_plane' => $this->publicQuestMap($quest->requiredPlane),
            'faction_map' => $this->publicQuestMap($quest->factionMap),
            'faction_loyalty_npc' => $this->publicQuestNpc($quest->factionLoyaltyNpc),
            'required_quest' => $this->publicRequiredQuest($quest->requiredQuest),
            'reward_item' => is_null($quest->rewardItem) ? null : [
                'id' => $quest->rewardItem->id,
                'name' => $quest->rewardItem->name,
            ],
        ];
    }

    private function publicRequiredQuest(?Quest $quest): ?array
    {
        if (is_null($quest)) {
            return null;
        }

        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'raid_id' => $quest->raid_id,
            'belongs_to_map_name' => $quest->belongs_to_map_name,
            'npc' => $this->publicQuestNpc($quest->npc),
            'raid' => is_null($quest->raid) ? null : [
                'id' => $quest->raid->id,
                'name' => $quest->raid->name,
            ],
        ];
    }

    private function publicQuestNpc(?Npc $npc): ?array
    {
        if (is_null($npc)) {
            return null;
        }

        return [
            'id' => $npc->id,
            'real_name' => $npc->real_name,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
            'must_be_at_same_location' => $npc->must_be_at_same_location,
            'game_map' => $this->publicQuestMap($npc->gameMap),
        ];
    }

    private function publicQuestMap(?GameMap $gameMap, int $depth = 0): ?array
    {
        if (is_null($gameMap)) {
            return null;
        }

        $requiredItem = $depth >= 4 ? null : $gameMap->map_required_item;

        return [
            'id' => $gameMap->id,
            'name' => $gameMap->name,
            'map_required_item' => $this->publicQuestItem($requiredItem, $depth + 1),
        ];
    }

    private function publicQuestItem(?Item $item, int $depth = 0): ?array
    {
        if (is_null($item)) {
            return null;
        }

        $requiredQuest = $item->required_quest;
        $requiredMonster = $item->required_monster;
        $dropLocation = $item->dropLocation;

        return [
            'id' => $item->id,
            'name' => $item->name,
            'drop_location_id' => $item->drop_location_id,
            'required_quest' => $depth >= 4 || is_null($requiredQuest) ? null : $this->publicRequiredQuest($requiredQuest),
            'required_monster' => is_null($requiredMonster) ? null : [
                'name' => $requiredMonster->name,
                'is_celestial_entity' => (bool) $requiredMonster->is_celestial_entity,
                'is_raid_boss' => (bool) $requiredMonster->is_raid_boss,
                'is_raid_monster' => (bool) $requiredMonster->is_raid_monster,
                'game_map' => $this->publicQuestMap($requiredMonster->gameMap, $depth + 1),
            ],
            'drop_location' => is_null($dropLocation) ? null : [
                'name' => $dropLocation->name,
                'x' => $dropLocation->x,
                'y' => $dropLocation->y,
                'hours_to_drop' => $dropLocation->hours_to_drop,
                'delve_enemy_strength_increase' => $dropLocation->delve_enemy_strength_increase,
                'map' => $this->publicQuestMap($dropLocation->map, $depth + 1),
            ],
            'locations' => collect($item->locations)->map(fn ($location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'x' => $location->x,
                'y' => $location->y,
                'map' => $this->publicQuestMap($location->map, $depth + 1),
            ])->values()->all(),
            'drop_sources' => collect($item->drop_sources)->map(fn (array $source): array => [
                'source_type' => $source['source_type'] ?? null,
                'monster_name' => $source['monster_name'] ?? null,
                'map_name' => $source['map_name'] ?? null,
                'location_name' => $source['location_name'] ?? null,
                'location_x' => $source['location_x'] ?? null,
                'location_y' => $source['location_y'] ?? null,
                'location_map' => $source['location_map'] ?? null,
            ])->values()->all(),
        ];
    }

    private function guideQuestDetails(QuestsCompleted $questCompletion, ?Character $viewerCharacter, array $viewerCompletedGuideQuestIds): array
    {
        $quest = $questCompletion->guideQuest;

        $viewerHasCompleted = ! is_null($viewerCharacter) && in_array($questCompletion->guide_quest_id, $viewerCompletedGuideQuestIds, true);

        $viewerHasUnlocked = false;

        if (! is_null($viewerCharacter) && ! is_null($quest)) {
            $viewerHasUnlocked = collect($this->guideQuestService->getCurrentQuestsForCharacter($viewerCharacter))
                ->contains(fn (GuideQuest $currentQuest): bool => $currentQuest->id === $quest->id);
        }

        $details = is_null($quest) ? [] : $quest->only([
            'id', 'name', 'intro_text', 'instructions', 'desktop_instructions', 'mobile_instructions',
            'required_level', 'required_reincarnation_amount', 'required_skill', 'required_skill_level',
            'required_secondary_skill', 'required_secondary_skill_level', 'required_faction_id', 'required_faction_level',
            'required_game_map_id', 'required_quest_id', 'required_quest_item_id', 'secondary_quest_item_id',
            'required_kingdoms', 'required_kingdom_level', 'required_kingdom_units', 'required_kingdom_building_id',
            'required_kingdom_building_level', 'required_passive_skill', 'required_passive_level', 'required_skill_type',
            'required_skill_type_level', 'required_class_specials_equipped', 'required_class_rank_level', 'required_gold',
            'required_gold_dust', 'required_shards', 'required_copper_coins', 'required_gold_bars', 'required_stats',
            'required_str', 'required_dex', 'required_int', 'required_dur', 'required_chr', 'required_agi', 'required_focus',
            'faction_points_per_kill', 'gold_dust_reward', 'shards_reward', 'gold_reward', 'xp_reward', 'unlock_at_level',
            'only_during_event', 'be_on_game_map', 'required_event_goal_participation',
            'required_event_goal_crafting_participation', 'required_event_goal_enchanting_participation',
            'required_holy_stacks', 'required_attached_gems', 'required_specialty_type', 'must_be_pledged_to_faction',
            'must_be_assisting_npc', 'required_fame_level', 'required_delve_survival_time', 'required_delve_pack_size',
            'required_batch_crafting_type', 'required_batch_crafting_hours', 'required_batch_crafted_items',
            'skill_name', 'faction_name', 'game_map_name', 'quest_name', 'quest_item_name', 'secondary_quest_item_name',
            'passive_name', 'secondary_skill_name', 'skill_type_name', 'kingdom_building_name', 'parent_quest_name',
            'required_to_be_on_game_map_name', 'required_batch_crafting_type_name', 'required_batch_crafted_item_names',
        ]);

        return [
            ...$details,
            'id' => $questCompletion->guide_quest_id,
            'completed_at' => $questCompletion->created_at?->toISOString(),
            'inspected_character_completed' => true,
            'viewer_has_completed' => $viewerHasCompleted,
            'viewer_has_unlocked' => $viewerHasUnlocked,
        ];
    }

    private function questCompletionChart($quests): array
    {
        $datedQuests = $quests->filter(fn (QuestsCompleted $quest): bool => ! is_null($quest->created_at));

        if ($datedQuests->isEmpty()) {
            return $this->emptyChart('quests_completed.created_at', 'completions', ['Quests', 'Guide Quests']);
        }

        $granularity = $this->chartGranularity($datedQuests->min('created_at'), $datedQuests->max('created_at'));

        return [
            'source' => 'quests_completed.created_at',
            'unit' => 'completions',
            'granularity' => $granularity,
            'series' => [
                [
                    'label' => 'Quests',
                    'points' => $this->cumulativeCountPoints($datedQuests->whereNotNull('quest_id'), 'created_at', $granularity),
                ],
                [
                    'label' => 'Guide Quests',
                    'points' => $this->cumulativeCountPoints($datedQuests->whereNotNull('guide_quest_id'), 'created_at', $granularity),
                ],
            ],
        ];
    }

    private function questSummaryChart(Character $character, $quests): array
    {
        $inspectedDates = $quests->filter(fn (QuestsCompleted $quest): bool => ! is_null($quest->created_at))->pluck('created_at');

        $otherCompletions = QuestsCompleted::where('character_id', '!=', $character->id)
            ->whereNotNull('created_at')
            ->get(['character_id', 'created_at']);

        $inspectedLabel = $character->name.' Completed Quests';
        $othersLabel = 'Average Completed Quests For Everyone Else';

        $allDates = $inspectedDates->merge($otherCompletions->pluck('created_at'));

        if ($allDates->isEmpty()) {
            return $this->emptyChart('quests_completed.created_at,characters.created_at', 'completions', [$inspectedLabel, $othersLabel]);
        }

        $granularity = $this->chartGranularity($allDates->min(), $allDates->max());
        $buckets = $this->bucketSequence($allDates->min(), $allDates->max(), $granularity);

        $otherCharacters = Character::where('id', '!=', $character->id)->get(['id', 'created_at']);

        $inspectedSorted = $inspectedDates->sort()->values();
        $inspectedIndex = 0;
        $inspectedCount = $inspectedSorted->count();
        $inspectedCumulative = 0;

        $otherCompletionsSorted = $otherCompletions->pluck('created_at')->sort()->values();
        $otherCompletionsIndex = 0;
        $otherCompletionsCount = $otherCompletionsSorted->count();
        $otherCompletionsCumulative = 0;

        $otherCharactersSorted = $otherCharacters->pluck('created_at')->filter(fn (?Carbon $createdAt): bool => ! is_null($createdAt))->sort()->values();
        $otherCharactersIndex = 0;
        $otherCharactersCount = $otherCharactersSorted->count();
        $otherCharactersCumulative = 0;

        $inspectedPoints = [];
        $averagePoints = [];

        foreach ($buckets as $bucketStart) {
            $bucketEnd = $this->bucketEnd($bucketStart, $granularity);

            while ($inspectedIndex < $inspectedCount && $inspectedSorted[$inspectedIndex]->lte($bucketEnd)) {
                $inspectedCumulative++;
                $inspectedIndex++;
            }

            $inspectedPoints[] = [
                'label' => $this->bucketLabel($bucketStart, $granularity),
                'date' => $bucketStart->toISOString(),
                'value' => $inspectedCumulative,
            ];

            while ($otherCharactersIndex < $otherCharactersCount && $otherCharactersSorted[$otherCharactersIndex]->lte($bucketEnd)) {
                $otherCharactersCumulative++;
                $otherCharactersIndex++;
            }

            while ($otherCompletionsIndex < $otherCompletionsCount && $otherCompletionsSorted[$otherCompletionsIndex]->lte($bucketEnd)) {
                $otherCompletionsCumulative++;
                $otherCompletionsIndex++;
            }

            $averagePoints[] = [
                'label' => $this->bucketLabel($bucketStart, $granularity),
                'date' => $bucketStart->toISOString(),
                'value' => $otherCharactersCumulative > 0 ? round($otherCompletionsCumulative / $otherCharactersCumulative, 2) : 0.0,
            ];
        }

        return [
            'source' => 'quests_completed.created_at,characters.created_at',
            'unit' => 'completions',
            'granularity' => $granularity,
            'series' => [
                ['label' => $inspectedLabel, 'points' => $inspectedPoints],
                ['label' => $othersLabel, 'points' => $averagePoints],
            ],
        ];
    }

    private function publicPassiveTree($passive): array
    {
        return [
            'id' => $passive->passive_skill_id,
            'parent_skill_id' => $passive->parent_skill_id,
            'name' => $passive->name,
            'current_level' => $passive->current_level,
            'max_level' => $passive->max_level,
            'hours_to_next' => $passive->hours_to_next,
            'is_locked' => (bool) $passive->is_locked,
            'quest_name' => $passive->quest_name,
            'is_quest_complete' => (bool) $passive->is_quest_complete,
            'passive_skill' => [
                'description' => $passive->passiveSkill?->description,
                'unlocks_at_level' => $passive->passiveSkill?->unlocks_at_level,
            ],
            'children' => $passive->children->map(fn ($child): array => $this->publicPassiveTree($child))->values()->all(),
        ];
    }

    private function loginCountChart(Character $character, $loginRows): array
    {
        if ($loginRows->isEmpty()) {
            return $this->emptyChart('user_login_durations.logged_in_at', 'count', ['Login Count']);
        }

        $granularity = $this->chartGranularity($this->loginChartStart($character, $loginRows), $loginRows->max('logged_in_at'));

        return [
            'source' => 'user_login_durations.logged_in_at',
            'unit' => 'count',
            'granularity' => $granularity,
            'series' => [
                ['label' => 'Login Count', 'points' => $this->loginCountPoints($loginRows, $granularity)],
            ],
        ];
    }

    private function loginDurationChart(Character $character, $loginRows): array
    {
        if ($loginRows->isEmpty()) {
            return $this->emptyChart('user_login_durations.logged_in_at', 'hours', ['Login Duration (Hours)']);
        }

        $intervals = $this->mergedLoginIntervals($loginRows);
        $latestEnd = collect($intervals)->max(fn (array $interval): Carbon => $interval['end']);
        $granularity = $this->chartGranularity($this->loginChartStart($character, $loginRows), $latestEnd ?? $loginRows->max('logged_in_at'));

        return [
            'source' => 'user_login_durations.logged_in_at',
            'unit' => 'hours',
            'granularity' => $granularity,
            'series' => [
                ['label' => 'Login Duration (Hours)', 'points' => $this->loginDurationHoursPoints($intervals, $granularity)],
            ],
        ];
    }

    private function loginChartStart(Character $character, $loginRows): Carbon
    {
        $registeredAt = $character->user?->created_at;

        if (! is_null($registeredAt)) {
            return $registeredAt;
        }

        return $loginRows->min('logged_in_at');
    }

    private function loginCountPoints($rows, string $granularity): array
    {
        $sorted = $rows->filter(fn ($row): bool => ! is_null($row->logged_in_at))->sortBy(fn ($row): int => $row->logged_in_at->getTimestamp())->values();

        $grouped = $sorted->groupBy(fn ($row): string => $this->bucketStart($row->logged_in_at, $granularity)->toISOString());

        return $grouped->map(function ($bucketRows, string $date) use ($granularity): array {
            return [
                'label' => $this->bucketLabel($bucketRows->first()->logged_in_at, $granularity),
                'date' => $date,
                'value' => $bucketRows->count(),
            ];
        })->values()->all();
    }

    private function mergedLoginIntervals($rows): array
    {
        $intervals = $rows->map(function ($row): ?array {
            $end = $row->logged_out_at ?? $row->last_heart_beat;

            if (is_null($row->logged_in_at) || is_null($end) || $end->lt($row->logged_in_at)) {
                return null;
            }

            return ['start' => $row->logged_in_at->copy(), 'end' => $end->copy()];
        })->filter()->sortBy(fn (array $interval): int => $interval['start']->getTimestamp())->values();

        $merged = [];

        foreach ($intervals as $interval) {
            $lastIndex = count($merged) - 1;

            if ($lastIndex < 0 || $interval['start']->gt($merged[$lastIndex]['end'])) {
                $merged[] = $interval;

                continue;
            }

            if ($interval['end']->gt($merged[$lastIndex]['end'])) {
                $merged[$lastIndex]['end'] = $interval['end'];
            }
        }

        return $merged;
    }

    private function loginDurationHoursPoints(array $intervals, string $granularity): array
    {
        if (empty($intervals)) {
            return [];
        }

        $buckets = $this->loginDurationBucketSequence($intervals[0]['start'], $intervals[count($intervals) - 1]['end'], $granularity);

        return collect($buckets)->map(function (Carbon $bucketStart) use ($intervals, $granularity): array {
            $bucketEnd = $this->nextLoginDurationBucketStart($bucketStart, $granularity);
            $seconds = 0;

            foreach ($intervals as $interval) {
                $start = $interval['start']->gt($bucketStart) ? $interval['start'] : $bucketStart;
                $end = $interval['end']->lt($bucketEnd) ? $interval['end'] : $bucketEnd;

                if ($end->gt($start)) {
                    $seconds += $start->diffInSeconds($end);
                }
            }

            return [
                'label' => $this->bucketLabel($bucketStart, $granularity),
                'date' => $bucketStart->toISOString(),
                'value' => (float) ($seconds / 3600),
                'seconds' => $seconds,
            ];
        })->all();
    }

    private function nextLoginDurationBucketStart(Carbon $bucketStart, string $granularity): Carbon
    {
        return match ($granularity) {
            'hours' => $bucketStart->copy()->addHour(),
            'days' => $bucketStart->copy()->addDay(),
            'weeks' => $bucketStart->copy()->addWeek(),
            'months' => $bucketStart->copy()->addMonthNoOverflow(),
            default => $bucketStart->copy()->addYear(),
        };
    }

    private function loginDurationBucketSequence(Carbon $oldest, Carbon $newest, string $granularity): array
    {
        $buckets = [];
        $cursor = $this->bucketStart($oldest, $granularity);

        while ($cursor->lt($newest)) {
            $buckets[] = $cursor->copy();
            $cursor = $this->nextLoginDurationBucketStart($cursor, $granularity);
        }

        return $buckets;
    }

    private function cumulativeCountPoints($rows, string $dateField, string $granularity): array
    {
        $sorted = $rows->filter(fn ($row) => ! is_null($row->{$dateField}))->sortBy(fn ($row): int => $row->{$dateField}->getTimestamp())->values();

        $grouped = $sorted->groupBy(fn ($row): string => $this->bucketStart($row->{$dateField}, $granularity)->toISOString());

        $runningTotal = 0;
        $points = [];

        foreach ($grouped as $date => $bucketRows) {
            $runningTotal += $bucketRows->count();

            $points[] = [
                'label' => $this->bucketLabel($bucketRows->first()->{$dateField}, $granularity),
                'date' => $date,
                'value' => $runningTotal,
            ];
        }

        return $points;
    }

    private function cumulativeSumPoints($rows, string $dateField, string $sumField, string $granularity): array
    {
        $sorted = $rows->filter(fn ($row) => ! is_null($row->{$dateField}))->sortBy(fn ($row): int => $row->{$dateField}->getTimestamp())->values();

        $grouped = $sorted->groupBy(fn ($row): string => $this->bucketStart($row->{$dateField}, $granularity)->toISOString());

        $runningTotal = 0;
        $points = [];

        foreach ($grouped as $date => $bucketRows) {
            $runningTotal += (int) $bucketRows->sum($sumField);

            $points[] = [
                'label' => $this->bucketLabel($bucketRows->first()->{$dateField}, $granularity),
                'date' => $date,
                'value' => $runningTotal,
            ];
        }

        return $points;
    }

    private function emptyChart(string $source, string $unit, array $labels): array
    {
        return [
            'source' => $source,
            'unit' => $unit,
            'granularity' => null,
            'series' => collect($labels)->map(fn (string $label): array => ['label' => $label, 'points' => []])->values()->all(),
        ];
    }

    private function chartGranularity(Carbon $oldest, Carbon $newest): string
    {
        $rangeDays = max(1, $oldest->diffInDays($newest) + 1);

        return match (true) {
            $oldest->diffInHours($newest) <= 48 => 'hours',
            $rangeDays <= 90 => 'days',
            $rangeDays <= 365 => 'weeks',
            $rangeDays <= 1095 => 'months',
            default => 'years',
        };
    }

    private function bucketStart(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'hours' => $date->copy()->startOfHour(),
            'days' => $date->copy()->startOfDay(),
            'weeks' => $date->copy()->startOfWeek(),
            'months' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfYear(),
        };
    }

    private function bucketEnd(Carbon $bucketStart, string $granularity): Carbon
    {
        return match ($granularity) {
            'hours' => $bucketStart->copy()->endOfHour(),
            'days' => $bucketStart->copy()->endOfDay(),
            'weeks' => $bucketStart->copy()->endOfWeek(),
            'months' => $bucketStart->copy()->endOfMonth(),
            default => $bucketStart->copy()->endOfYear(),
        };
    }

    private function bucketLabel(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'hours' => $date->format('Y-m-d H:00'),
            'days' => $date->format('Y-m-d'),
            'weeks' => $date->copy()->startOfWeek()->format('Y-m-d'),
            'months' => $date->format('Y-m'),
            default => $date->format('Y'),
        };
    }

    private function bucketSequence(Carbon $oldest, Carbon $newest, string $granularity): array
    {
        $buckets = [];
        $cursor = $this->bucketStart($oldest, $granularity);
        $end = $this->bucketStart($newest, $granularity);

        while ($cursor->lte($end)) {
            $buckets[] = $cursor->copy();

            $cursor = match ($granularity) {
                'hours' => $cursor->addHour(),
                'days' => $cursor->addDay(),
                'weeks' => $cursor->addWeek(),
                'months' => $cursor->addMonthNoOverflow(),
                default => $cursor->addYear(),
            };
        }

        return $buckets;
    }

    private function statBreakdownSummary(Character $character, array $statDetails): array
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
