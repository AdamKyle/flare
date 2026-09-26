import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useCallback, useEffect } from 'react';

import UseOwnGameMapMoveDefinition from './definitions/use-own-game-map-move-definition';
import UseOwnGameMapMoveOptions from './types/use-own-game-map-move-options';
import { useGameMapMoveEmitter } from './use-game-map-move-emitter';
import { useGameMapMoveOrchestration } from '../../hooks/use-game-map-move-orchestration';
import { GameMapMoveEventMap } from '../definitions/game-map-move-event-map';
import { GameMapMoveEvent } from '../enums/game-map-move-event';

export const useOwnGameMapMove = ({
  game_map_id: gameMapId,
  on_move_succeeded: onMoveSucceeded,
}: UseOwnGameMapMoveOptions): UseOwnGameMapMoveDefinition => {
  const eventSystem = useEventSystem();
  const commands = useGameMapMoveEmitter();
  const move = useGameMapMoveOrchestration();

  const {
    start_move_location: startMoveLocation,
    start_move_npc: startMoveNpc,
    select_move_target: selectMoveTarget,
    confirm_move: confirmMove,
    cancel_move: cancelMove,
    clear_move_error: clearMoveError,
    moving_record: movingRecord,
    pending_move_target: pendingMoveTarget,
    is_moving: isMoving,
    move_error: moveError,
  } = move;

  const handleStartLocation = useCallback(
    (command: GameMapMoveEventMap[GameMapMoveEvent.START_LOCATION]): void => {
      startMoveLocation(
        command.record_id,
        command.label,
        command.origin_x,
        command.origin_y
      );
    },
    [startMoveLocation]
  );
  const handleStartNpc = useCallback(
    (command: GameMapMoveEventMap[GameMapMoveEvent.START_NPC]): void => {
      startMoveNpc(
        command.record_id,
        command.label,
        command.origin_x,
        command.origin_y
      );
    },
    [startMoveNpc]
  );
  const handleConfirm = useCallback(async (): Promise<void> => {
    const moved = await confirmMove(gameMapId);

    if (!moved || !movingRecord) {
      return;
    }

    await onMoveSucceeded();
    commands.emit_succeeded({ moving_record: movingRecord });
  }, [commands, confirmMove, gameMapId, movingRecord, onMoveSucceeded]);

  useEffect(() => {
    const emitter = eventSystem.fetchOrCreateEventEmitter<GameMapMoveEventMap>(
      GameMapMoveEvent.STATE_CHANGED
    );

    emitter.on(GameMapMoveEvent.START_LOCATION, handleStartLocation);
    emitter.on(GameMapMoveEvent.START_NPC, handleStartNpc);
    emitter.on(GameMapMoveEvent.SELECT_TARGET, selectMoveTarget);
    emitter.on(GameMapMoveEvent.CONFIRM, handleConfirm);
    emitter.on(GameMapMoveEvent.CANCEL, cancelMove);
    emitter.on(GameMapMoveEvent.CLEAR_ERROR, clearMoveError);

    return () => {
      emitter.off(GameMapMoveEvent.START_LOCATION, handleStartLocation);
      emitter.off(GameMapMoveEvent.START_NPC, handleStartNpc);
      emitter.off(GameMapMoveEvent.SELECT_TARGET, selectMoveTarget);
      emitter.off(GameMapMoveEvent.CONFIRM, handleConfirm);
      emitter.off(GameMapMoveEvent.CANCEL, cancelMove);
      emitter.off(GameMapMoveEvent.CLEAR_ERROR, clearMoveError);
    };
  }, [
    eventSystem,
    handleConfirm,
    handleStartLocation,
    handleStartNpc,
    cancelMove,
    clearMoveError,
    selectMoveTarget,
  ]);

  useEffect(() => {
    commands.emit_state({
      moving_record: movingRecord,
      pending_move_target: pendingMoveTarget,
      is_moving: isMoving,
      move_error: moveError,
    });
  }, [commands, isMoving, moveError, movingRecord, pendingMoveTarget]);

  return move;
};
