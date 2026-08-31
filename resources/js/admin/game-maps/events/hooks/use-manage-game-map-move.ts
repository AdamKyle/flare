import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useCallback, useEffect, useMemo, useState } from 'react';

import UseManageGameMapMoveDefinition from './definitions/use-manage-game-map-move-definition';
import { useGameMapMoveEmitter } from './use-game-map-move-emitter';
import {
  GameMapMoveEventMap,
  GameMapMoveStateDefinition,
} from '../definitions/game-map-move-event-map';
import { GameMapMoveEvent } from '../enums/game-map-move-event';
import { buildDefaultGameMapMoveState } from '../utils/build-default-game-map-move-state';

/**
 * Subscribe to the authoritative editor movement state and emit movement commands.
 */
export const useManageGameMapMove = (
  initial_state?: GameMapMoveStateDefinition | null
): UseManageGameMapMoveDefinition => {
  const eventSystem = useEventSystem();
  const commands = useGameMapMoveEmitter();

  const [sharedState, setSharedState] = useState<GameMapMoveStateDefinition>(
    () => initial_state ?? buildDefaultGameMapMoveState()
  );

  useEffect(() => {
    const emitter = eventSystem.fetchOrCreateEventEmitter<GameMapMoveEventMap>(
      GameMapMoveEvent.STATE_CHANGED
    );

    const handleStateChanged = (state: GameMapMoveStateDefinition): void => {
      setSharedState(state);
    };

    emitter.on(GameMapMoveEvent.STATE_CHANGED, handleStateChanged);

    return () => {
      emitter.off(GameMapMoveEvent.STATE_CHANGED, handleStateChanged);
    };
  }, [eventSystem]);

  const startMoveLocation = useCallback(
    (
      recordId: number,
      label: string,
      originX: number,
      originY: number
    ): void => {
      commands.start_location({
        record_id: recordId,
        label,
        origin_x: originX,
        origin_y: originY,
      });
    },
    [commands]
  );
  const startMoveNpc = useCallback(
    (
      recordId: number,
      label: string,
      originX: number,
      originY: number
    ): void => {
      commands.start_npc({
        record_id: recordId,
        label,
        origin_x: originX,
        origin_y: originY,
      });
    },
    [commands]
  );

  return useMemo(
    () => ({
      moving_record: sharedState.moving_record,
      pending_move_target: sharedState.pending_move_target,
      is_moving: sharedState.is_moving,
      move_error: sharedState.move_error,
      start_move_location: startMoveLocation,
      start_move_npc: startMoveNpc,
      select_move_target: commands.select_target,
      confirm_move: commands.confirm,
      cancel_move: commands.cancel,
      clear_move_error: commands.clear_error,
    }),
    [commands, sharedState, startMoveLocation, startMoveNpc]
  );
};
