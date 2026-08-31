export default interface QuestFormStateDefinition {
  name: string;
  npc_id: number | null;
  raid_id: number | null;
  only_for_event: number | null;
  before_completion_description: string;
  after_completion_description: string;

  parent_quest_id: number | null;
  required_quest_id: number | null;
  required_quest_chain: number[];
  reincarnated_times: string;

  item_id: number | null;
  secondary_required_item: number | null;
  access_to_map_id: number | null;
  faction_game_map_id: number | null;
  required_faction_level: string;
  assisting_npc_id: number | null;
  required_fame_level: string;
  gold_cost: string;
  gold_dust_cost: string;
  shard_cost: string;
  copper_coin_cost: string;

  reward_item: number | null;
  reward_gold: string;
  reward_gold_dust: string;
  reward_shards: string;
  reward_xp: string;
  unlocks_skill: boolean;
  unlocks_skill_type: number | null;
  unlocks_feature: number | null;
  unlocks_passive_id: number | null;
}
