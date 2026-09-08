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

export interface QuestItemFactualNavigationDefinition {
  on_open_item?: (id: number) => void;
  on_open_location?: (id: number) => void;
  on_open_map?: (id: number) => void;
  on_open_npc?: (id: number) => void;
  on_open_quest?: (id: number) => void;
  on_open_monster?: (id: number) => void;
}

export default interface QuestItemFactualDefinition {
  name: string;
  description: string;
  type: string;
  usable: boolean;
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
