export interface AdminQuestItemLocationDefinition {
  id: number;
  name: string;
  map: string;
}

export interface AdminQuestItemMonsterDefinition {
  id: number;
  name: string;
  map: string;
}

export interface AdminQuestItemQuestDefinition {
  id: number;
  name: string;
  npc: string;
  map: string;
}

/**
 * Matches App\Game\Core\Items\Transformers\QuestItemTransformer::transform()
 * exactly. Used for both the admin Item Show quest presentation and the
 * Location/NPC quest-Item relationship summaries.
 */
export default interface AdminQuestItemPresentationDefinition {
  item_id: number;
  name: string;
  type: string;
  description: string;
  can_drop: boolean;
  usable: boolean;
  craft_only: boolean;
  move_time_out_mod_bonus: number | null;
  fight_time_out_mod_bonus: number | null;
  effect: string | null;
  drop_location: AdminQuestItemLocationDefinition | null;
  required_monster: AdminQuestItemMonsterDefinition | null;
  required_quest: AdminQuestItemQuestDefinition | null;
  reward_locations: AdminQuestItemLocationDefinition[];
  required_quests: AdminQuestItemQuestDefinition[];
  reward_quests: AdminQuestItemQuestDefinition[];
  required_locations: AdminQuestItemLocationDefinition[];
}
