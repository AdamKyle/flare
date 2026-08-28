import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';
import { MovingRecordDefinition } from '../types/game-map-editor-canvas-props';

/**
 * Resolve the human-readable entity type label ("Location" or "Npc") for the record
 * currently being moved on a Game Map.
 */
export const resolveMovingRecordTypeLabel = (
  movingRecord: MovingRecordDefinition
): string =>
  movingRecord.kind === GameMapMarkerVariant.Npc ? 'Npc' : 'Location';
