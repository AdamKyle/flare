export default interface QuestFormDefinition {
  id: number;
  name: string;
  npc_id: number | null;
  raid_id: number | null;
  only_for_event: number | null;
  before_completion_description: string | null;
  after_completion_description: string | null;

  parent_quest_id: number | null;
  required_quest_id: number | null;
  required_quest_chain: number[];
  reincarnated_times: number | null;

  item_id: number | null;
  secondary_required_item: number | null;
  access_to_map_id: number | null;
  faction_game_map_id: number | null;
  required_faction_level: number | null;
  assisting_npc_id: number | null;
  required_fame_level: number | null;
  gold_cost: number | null;
  gold_dust_cost: number | null;
  shard_cost: number | null;
  copper_coin_cost: number | null;

  reward_item: number | null;
  reward_gold: number | null;
  reward_gold_dust: number | null;
  reward_shards: number | null;
  reward_xp: number | null;
  unlocks_skill: boolean;
  unlocks_skill_type: number | null;
  unlocks_feature: number | null;
  unlocks_passive_id: number | null;
}
