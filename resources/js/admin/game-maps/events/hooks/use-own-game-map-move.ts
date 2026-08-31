import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useCallback, useEffect, useMemo } from 'react';

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

  const handleStartLocation = useCallback(
    (command: GameMapMoveEventMap[GameMapMoveEvent.START_LOCATION]): void => {
      move.start_move_location(
        command.record_id,
        command.label,
        command.origin_x,
        command.origin_y
      );
    },
    [move.start_move_location]
  );
  const handleStartNpc = useCallback(
    (command: GameMapMoveEventMap[GameMapMoveEvent.START_NPC]): void => {
      move.start_move_npc(
        command.record_id,
        command.label,
        command.origin_x,
        command.origin_y
      );
    },
    [move.start_move_npc]
  );
  const handleConfirm = useCallback(async (): Promise<void> => {
    const movingRecord = move.moving_record;
    const moved = await move.confirm_move(gameMapId);

    if (!moved || !movingRecord) {
      return;
    }

    await onMoveSucceeded();
    commands.emit_succeeded({ moving_record: movingRecord });
  }, [
    commands,
    gameMapId,
    move.confirm_move,
    move.moving_record,
    onMoveSucceeded,
  ]);

  useEffect(() => {
    const emitter = eventSystem.fetchOrCreateEventEmitter<GameMapMoveEventMap>(
      GameMapMoveEvent.STATE_CHANGED
    );

    emitter.on(GameMapMoveEvent.START_LOCATION, handleStartLocation);
    emitter.on(GameMapMoveEvent.START_NPC, handleStartNpc);
    emitter.on(GameMapMoveEvent.SELECT_TARGET, move.select_move_target);
    emitter.on(GameMapMoveEvent.CONFIRM, handleConfirm);
    emitter.on(GameMapMoveEvent.CANCEL, move.cancel_move);
    emitter.on(GameMapMoveEvent.CLEAR_ERROR, move.clear_move_error);

    return () => {
      emitter.off(GameMapMoveEvent.START_LOCATION, handleStartLocation);
      emitter.off(GameMapMoveEvent.START_NPC, handleStartNpc);
      emitter.off(GameMapMoveEvent.SELECT_TARGET, move.select_move_target);
      emitter.off(GameMapMoveEvent.CONFIRM, handleConfirm);
      emitter.off(GameMapMoveEvent.CANCEL, move.cancel_move);
      emitter.off(GameMapMoveEvent.CLEAR_ERROR, move.clear_move_error);
    };
  }, [
    eventSystem,
    handleConfirm,
    handleStartLocation,
    handleStartNpc,
    move.cancel_move,
    move.clear_move_error,
    move.select_move_target,
  ]);

  useEffect(() => {
    commands.emit_state({
      moving_record: move.moving_record,
      pending_move_target: move.pending_move_target,
      is_moving: move.is_moving,
      move_error: move.move_error,
    });
  }, [
    commands,
    move.is_moving,
    move.move_error,
    move.moving_record,
    move.pending_move_target,
  ]);

  return useMemo(() => move, [move]);
};
