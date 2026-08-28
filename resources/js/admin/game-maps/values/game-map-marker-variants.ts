import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';

export const GAME_MAP_MARKER_ICON: Record<GameMapMarkerVariant, string> = {
  [GameMapMarkerVariant.Location]: 'fas fa-map-marker-alt',
  [GameMapMarkerVariant.Npc]: 'fas fa-user',
  [GameMapMarkerVariant.Kingdom]: 'fas fa-chess-rook',
};

export const GAME_MAP_MARKER_COLOR: Record<GameMapMarkerVariant, string> = {
  [GameMapMarkerVariant.Location]: 'text-danube-600 dark:text-danube-300',
  [GameMapMarkerVariant.Npc]: 'text-mango-tango-600 dark:text-mango-tango-300',
  [GameMapMarkerVariant.Kingdom]: 'text-indigo-600 dark:text-indigo-300',
};
