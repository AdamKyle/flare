import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';
import { MovingRecordDefinition } from '../types/game-map-editor-canvas-props';

export const resolveMovingRecordTypeLabel = (
  movingRecord: MovingRecordDefinition
): string =>
  movingRecord.kind === GameMapMarkerVariant.Npc ? 'Npc' : 'Location';
