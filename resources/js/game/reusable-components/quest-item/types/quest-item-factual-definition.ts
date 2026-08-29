export interface QuestItemFactualLocationDefinition {
  id: number;
  name: string;
  map: string;
}

export interface QuestItemFactualMonsterDefinition {
  id: number;
  name: string;
  map: string;
}

export interface QuestItemFactualQuestDefinition {
  id: number;
  name: string;
  npc: string;
  map: string;
}

/**
 * The smallest permission-neutral factual Quest Item shape rendered by
 * `ItemMetaSection` + `QuestItemDetails` and their partials. Deliberately
 * narrower than `BaseQuestItemDefinition` (which also carries inventory-slot
 * fields such as `slot_id`/`min_list_price` that this factual presentation
 * never reads), so both the player-facing inventory Quest Item and the
 * permission-neutral Admin/Location Quest Item presentation can share this
 * exact rendering without fabricating inventory-slot state.
 */
export default interface QuestItemFactualDefinition {
  name: string;
  description: string;
  type: string;
  effect: string | null;
  move_time_out_mod_bonus: number | null;
  fight_time_out_mod_bonus: number | null;
  drop_location: QuestItemFactualLocationDefinition | null;
  required_monster: QuestItemFactualMonsterDefinition | null;
  required_quest: QuestItemFactualQuestDefinition | null;
  required_quests: QuestItemFactualQuestDefinition[];
  reward_quests: QuestItemFactualQuestDefinition[];
  reward_locations: QuestItemFactualLocationDefinition[];
  required_locations: QuestItemFactualLocationDefinition[];
}
