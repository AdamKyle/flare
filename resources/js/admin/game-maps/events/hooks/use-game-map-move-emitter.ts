import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useCallback, useMemo } from 'react';

import UseGameMapMoveEmitterDefinition from './definitions/use-game-map-move-emitter-definition';
import CoordinateDefinition from '../../types/coordinate-definition';
import {
  GameMapMoveStartCommandDefinition,
  GameMapMoveEventMap,
  GameMapMoveStateDefinition,
  GameMapMoveSucceededDefinition,
} from '../definitions/game-map-move-event-map';
import { GameMapMoveEvent } from '../enums/game-map-move-event';

export const useGameMapMoveEmitter = (): UseGameMapMoveEmitterDefinition => {
  const eventSystem = useEventSystem();

  const emitter = eventSystem.fetchOrCreateEventEmitter<GameMapMoveEventMap>(
    GameMapMoveEvent.STATE_CHANGED
  );

  const emitState = useCallback(
    (state: GameMapMoveStateDefinition): void => {
      emitter.emit(GameMapMoveEvent.STATE_CHANGED, state);
    },
    [emitter]
  );

  const startLocation = useCallback(
    (command: GameMapMoveStartCommandDefinition): void => {
      emitter.emit(GameMapMoveEvent.START_LOCATION, command);
    },
    [emitter]
  );
  const startNpc = useCallback(
    (command: GameMapMoveStartCommandDefinition): void => {
      emitter.emit(GameMapMoveEvent.START_NPC, command);
    },
    [emitter]
  );
  const selectTarget = useCallback(
    (coordinate: CoordinateDefinition): void => {
      emitter.emit(GameMapMoveEvent.SELECT_TARGET, coordinate);
    },
    [emitter]
  );
  const confirm = useCallback((): void => {
    emitter.emit(GameMapMoveEvent.CONFIRM, undefined);
  }, [emitter]);
  const cancel = useCallback((): void => {
    emitter.emit(GameMapMoveEvent.CANCEL, undefined);
  }, [emitter]);
  const clearError = useCallback((): void => {
    emitter.emit(GameMapMoveEvent.CLEAR_ERROR, undefined);
  }, [emitter]);
  const emitSucceeded = useCallback(
    (result: GameMapMoveSucceededDefinition): void => {
      emitter.emit(GameMapMoveEvent.SUCCEEDED, result);
    },
    [emitter]
  );

  return useMemo(
    () => ({
      emit_state: emitState,
      start_location: startLocation,
      start_npc: startNpc,
      select_target: selectTarget,
      confirm,
      cancel,
      clear_error: clearError,
      emit_succeeded: emitSucceeded,
    }),
    [
      cancel,
      clearError,
      confirm,
      emitState,
      emitSucceeded,
      selectTarget,
      startLocation,
      startNpc,
    ]
  );
};
