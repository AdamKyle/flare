import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useCallback, useMemo, useState } from 'react';

import UseGameMapMoveOrchestrationDefinition from './definitions/use-game-map-move-orchestration-definition';
import { useMoveLocation } from '../../locations/api/hooks/use-move-location';
import { useMoveNpc } from '../../npcs/api/hooks/use-move-npc';
import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';
import CoordinateDefinition from '../types/coordinate-definition';
import { MovingRecordDefinition } from '../types/game-map-editor-canvas-props';

/**
 * Preserve one move state across separately mounted editor and SidePeek consumers.
 */
export const useGameMapMoveOrchestration = (
  initialMovingRecord?: MovingRecordDefinition | null,
  initialPendingMoveTarget?: CoordinateDefinition | null
): UseGameMapMoveOrchestrationDefinition => {
  const {
    moving: movingLocation,
    error: moveLocationError,
    field_errors: moveLocationFieldErrors,
    move: moveLocation,
    clear_error: clearMoveLocationError,
  } = useMoveLocation();
  const {
    moving: movingNpc,
    error: moveNpcError,
    field_errors: moveNpcFieldErrors,
    move: moveNpc,
    clear_error: clearMoveNpcError,
  } = useMoveNpc();

  const [movingRecord, setMovingRecord] =
    useState<MovingRecordDefinition | null>(initialMovingRecord ?? null);
  const [pendingMoveTarget, setPendingMoveTarget] =
    useState<CoordinateDefinition | null>(initialPendingMoveTarget ?? null);

  const isMoving = movingLocation || movingNpc;

  const resolveMoveError = (): AxiosErrorDefinition | null => {
    if (movingRecord?.kind === GameMapMarkerVariant.Npc) {
      return moveNpcError;
    }

    return moveLocationError;
  };

  const resolveMoveFieldErrors = (): Record<string, string> => {
    if (movingRecord?.kind === GameMapMarkerVariant.Npc) {
      return moveNpcFieldErrors;
    }

    return moveLocationFieldErrors;
  };

  const moveError = resolveMoveError();
  const moveFieldErrors = resolveMoveFieldErrors();

  const startMoveLocation = useCallback(
    (
      locationId: number,
      label: string,
      originX: number,
      originY: number
    ): void => {
      clearMoveLocationError();
      setPendingMoveTarget(null);
      setMovingRecord({
        kind: GameMapMarkerVariant.Location,
        id: locationId,
        label,
        origin_x: originX,
        origin_y: originY,
      });
    },
    [clearMoveLocationError]
  );

  const startMoveNpc = useCallback(
    (npcId: number, label: string, originX: number, originY: number): void => {
      clearMoveNpcError();
      setPendingMoveTarget(null);
      setMovingRecord({
        kind: GameMapMarkerVariant.Npc,
        id: npcId,
        label,
        origin_x: originX,
        origin_y: originY,
      });
    },
    [clearMoveNpcError]
  );

  const selectMoveTarget = useCallback(
    (coordinate: CoordinateDefinition): void => {
      setPendingMoveTarget(coordinate);
    },
    []
  );

  const cancelMove = useCallback((): void => {
    setMovingRecord(null);
    setPendingMoveTarget(null);
    clearMoveLocationError();
    clearMoveNpcError();
  }, [clearMoveLocationError, clearMoveNpcError]);

  const clearMoveError = useCallback((): void => {
    clearMoveLocationError();
    clearMoveNpcError();
  }, [clearMoveLocationError, clearMoveNpcError]);

  const moveLocationToPendingTarget = useCallback(
    async (
      gameMapId: number,
      recordId: number,
      target: CoordinateDefinition
    ): Promise<boolean> => {
      const moved = await moveLocation(gameMapId, recordId, {
        x: target.x_value,
        y: target.y_value,
      });

      return Boolean(moved);
    },
    [moveLocation]
  );

  const moveNpcToPendingTarget = useCallback(
    async (
      gameMapId: number,
      recordId: number,
      target: CoordinateDefinition
    ): Promise<boolean> => {
      const moved = await moveNpc(gameMapId, recordId, {
        x_position: target.x_value,
        y_position: target.y_value,
      });

      return Boolean(moved);
    },
    [moveNpc]
  );

  const moveRecordToPendingTarget = useCallback(
    (
      gameMapId: number,
      record: MovingRecordDefinition,
      target: CoordinateDefinition
    ): Promise<boolean> => {
      if (record.kind === GameMapMarkerVariant.Npc) {
        return moveNpcToPendingTarget(gameMapId, record.id, target);
      }

      return moveLocationToPendingTarget(gameMapId, record.id, target);
    },
    [moveLocationToPendingTarget, moveNpcToPendingTarget]
  );

  const confirmMove = useCallback(
    async (gameMapId: number): Promise<boolean> => {
      if (!movingRecord || !pendingMoveTarget || isMoving) {
        return false;
      }

      const moved = await moveRecordToPendingTarget(
        gameMapId,
        movingRecord,
        pendingMoveTarget
      );

      if (!moved) {
        return false;
      }

      setMovingRecord(null);
      setPendingMoveTarget(null);

      return true;
    },
    [isMoving, moveRecordToPendingTarget, movingRecord, pendingMoveTarget]
  );

  return useMemo(
    () => ({
      moving_record: movingRecord,
      pending_move_target: pendingMoveTarget,
      is_moving: isMoving,
      move_error: moveError,
      move_field_errors: moveFieldErrors,
      start_move_location: startMoveLocation,
      start_move_npc: startMoveNpc,
      select_move_target: selectMoveTarget,
      confirm_move: confirmMove,
      cancel_move: cancelMove,
      clear_move_error: clearMoveError,
    }),
    [
      cancelMove,
      clearMoveError,
      confirmMove,
      isMoving,
      moveError,
      moveFieldErrors,
      movingRecord,
      pendingMoveTarget,
      selectMoveTarget,
      startMoveLocation,
      startMoveNpc,
    ]
  );
};
