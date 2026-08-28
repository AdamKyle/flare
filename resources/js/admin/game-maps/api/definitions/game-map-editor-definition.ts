import GameMapKingdomMarkerDefinition from './game-map-kingdom-marker-definition';
import GameMapLocationMarkerDefinition from './game-map-location-marker-definition';
import GameMapNpcMarkerDefinition from './game-map-npc-marker-definition';

export type GameMapTile = string[];

export interface GameMapRequiredQuestItemQuestDefinition {
  id: number;
  name: string;
}

export interface GameMapRequiredQuestItemDefinition {
  id: number;
  name: string;
  quest: GameMapRequiredQuestItemQuestDefinition | null;
}

export interface GameMapDetailDefinition {
  id: number;
  name: string;
  map_url: string;
  tiles: GameMapTile[];
  description: string | null;
  kingdom_color: string;
  default: boolean;
  can_traverse: boolean;
  event_restriction: GameMapEventRestrictionDefinition | null;
  xp_bonus: number | null;
  skill_training_bonus: number | null;
  drop_chance_bonus: number | null;
  enemy_stat_bonus: number | null;
  character_attack_reduction: number | null;
  required_location: GameMapRequiredLocationDefinition | null;
  required_quest_item: GameMapRequiredQuestItemDefinition | null;
}

export interface GameMapEventRestrictionDefinition {
  value: number;
  label: string;
}

export interface GameMapRequiredLocationDefinition {
  id: number;
  name: string;
}

export interface GameMapEditorMapDefinition {
  id: number;
  name: string;
  map_url: string;
  tiles: GameMapTile[];
}

export interface GameMapCoordinatesDefinition {
  x: number[];
  y: number[];
}

export default interface GameMapEditorDefinition {
  game_map: GameMapEditorMapDefinition;
  coordinates: GameMapCoordinatesDefinition;
  locations: GameMapLocationMarkerDefinition[];
  npcs: GameMapNpcMarkerDefinition[];
  kingdoms: GameMapKingdomMarkerDefinition[];
}
