import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';
import GameMapEditorLegendEntry from '../types/game-map-editor-legend-entry';

export const GAME_MAP_EDITOR_LEGEND_ENTRIES: GameMapEditorLegendEntry[] = [
  { variant: GameMapMarkerVariant.Location, label: 'Location' },
  { variant: GameMapMarkerVariant.Npc, label: 'Npc' },
  { variant: GameMapMarkerVariant.Kingdom, label: 'Kingdom' },
];
