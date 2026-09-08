import GameMapKingdomMarkerDefinition from './game-map-kingdom-marker-definition';
import GameMapLocationMarkerDefinition from './game-map-location-marker-definition';
import GameMapNpcMarkerDefinition from './game-map-npc-marker-definition';
import GameMapFactualDefinition, {
  GameMapRequiredLocationDefinition,
  GameMapRequiredQuestItemDefinition,
  GameMapRequiredQuestItemQuestDefinition,
} from '../../../../game/reusable-components/game-map/types/game-map-factual-definition';

export type GameMapTile = string[];

export {
  GameMapRequiredLocationDefinition,
  GameMapRequiredQuestItemDefinition,
  GameMapRequiredQuestItemQuestDefinition,
};

export interface GameMapDetailDefinition extends GameMapFactualDefinition {
  tiles: GameMapTile[];
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
