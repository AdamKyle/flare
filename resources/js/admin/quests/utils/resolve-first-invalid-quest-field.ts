import QuestFormErrorsDefinition from '../definitions/quest-form-errors-definition';
import QuestFormStateDefinition from '../definitions/quest-form-state-definition';

export const QUEST_STEP_FIELD_IDS: ReadonlyArray<
  ReadonlyArray<{ field: keyof QuestFormStateDefinition; id: string }>
> = [
  [
    { field: 'name', id: 'quest-name' },
    { field: 'npc_id', id: 'quest-npc' },
    { field: 'raid_id', id: 'quest-raid' },
    { field: 'only_for_event', id: 'quest-event' },
  ],
  [
    { field: 'parent_quest_id', id: 'quest-parent' },
    { field: 'required_quest_id', id: 'quest-required-quest' },
    { field: 'required_quest_chain', id: 'quest-required-chain' },
    { field: 'reincarnated_times', id: 'quest-reincarnated-times' },
  ],
  [
    { field: 'item_id', id: 'quest-primary-item' },
    { field: 'secondary_required_item', id: 'quest-secondary-item' },
    { field: 'access_to_map_id', id: 'quest-access-map' },
    { field: 'faction_game_map_id', id: 'quest-faction-map' },
    { field: 'required_faction_level', id: 'quest-required-faction-level' },
    { field: 'assisting_npc_id', id: 'quest-assisting-npc' },
    { field: 'required_fame_level', id: 'quest-required-fame-level' },
    { field: 'gold_cost', id: 'quest-gold-cost' },
    { field: 'gold_dust_cost', id: 'quest-gold-dust-cost' },
    { field: 'shard_cost', id: 'quest-shard-cost' },
    { field: 'copper_coin_cost', id: 'quest-copper-coin-cost' },
  ],
  [
    { field: 'reward_item', id: 'quest-reward-item' },
    { field: 'reward_gold', id: 'quest-reward-gold' },
    { field: 'reward_gold_dust', id: 'quest-reward-gold-dust' },
    { field: 'reward_shards', id: 'quest-reward-shards' },
    { field: 'reward_xp', id: 'quest-reward-xp' },
    { field: 'unlocks_skill_type', id: 'quest-unlocks-skill-type' },
    { field: 'unlocks_feature', id: 'quest-unlocks-feature' },
    { field: 'unlocks_passive_id', id: 'quest-unlocks-passive' },
  ],
];

export const resolveFirstInvalidQuestField = (
  errors: QuestFormErrorsDefinition,
  stepIndex: number
): string | null =>
  QUEST_STEP_FIELD_IDS[stepIndex]?.find(({ field }) => field in errors)?.id ??
  null;
