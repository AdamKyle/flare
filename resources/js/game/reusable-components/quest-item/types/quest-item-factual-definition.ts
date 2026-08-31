export interface GameMapIdentityDefinition {
  id: number;
  name: string;
}

export interface LocationIdentityDefinition {
  id: number;
  name: string;
  game_map: GameMapIdentityDefinition;
}

export interface NpcIdentityDefinition {
  id: number;
  name: string;
  type: number;
  game_map: GameMapIdentityDefinition;
  x_position: number;
  y_position: number;
}

export interface QuestIdentityDefinition {
  id: number;
  name: string;
  npc: NpcIdentityDefinition | null;
  game_map: GameMapIdentityDefinition | null;
}

export interface MonsterIdentityDefinition {
  id: number;
  name: string;
  game_map: GameMapIdentityDefinition;
  quest_item_drop_chance: number | null;
}

/**
 * Optional read-only navigation callbacks accepted by the shared factual
 * Quest Item presentation and its partials. A relationship identity renders
 * as an accessible interactive control only when its callback is supplied;
 * otherwise it renders as plain factual text. Never checks Admin permission,
 * imports Admin APIs, or mutates data.
 */
export interface QuestItemFactualNavigationDefinition {
  on_open_item?: (id: number) => void;
  on_open_location?: (id: number) => void;
  on_open_map?: (id: number) => void;
  on_open_npc?: (id: number) => void;
  on_open_quest?: (id: number) => void;
  on_open_monster?: (id: number) => void;
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
  drop_location: LocationIdentityDefinition | null;
  required_monsters: MonsterIdentityDefinition[];
  required_quest: QuestIdentityDefinition | null;
  required_quests: QuestIdentityDefinition[];
  reward_quests: QuestIdentityDefinition[];
  reward_locations: LocationIdentityDefinition[];
  required_locations: LocationIdentityDefinition[];
}
