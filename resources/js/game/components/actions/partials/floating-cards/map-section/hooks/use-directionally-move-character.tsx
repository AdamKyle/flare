import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { MapActions } from '../event-types/map-actions';
import { MapMovementTypes } from '../map-movement-types/map-movement-types';
import UseDirectionallyMoveCharacter from './definitions/use-directionally-move-character';

export const useDirectionallyMoveCharacter =
  (): UseDirectionallyMoveCharacter => {
    const eventSystem = useEventSystem();

    const [movementAmount, setMovementAmount] = useState(0);
    const [movementType, setMovementType] = useState<MapMovementTypes | null>(
      null
    );

    const moveCharacterEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: [number, MapMovementTypes];
    }>(MapActions.MOVE_CHARACTER);

    useEffect(() => {
      const updateMovement = (amount: number, type: MapMovementTypes) => {
        setMovementAmount(amount);
        setMovementType(type);
      };

      moveCharacterEmitter.on(MapActions.MOVE_CHARACTER, updateMovement);

      return () => {
        moveCharacterEmitter.off(MapActions.MOVE_CHARACTER, updateMovement);
      };
    }, [moveCharacterEmitter]);

    const moveCharacterDirectionally = (
      amount: number,
      direction: MapMovementTypes
    ) => {
      moveCharacterEmitter.emit(MapActions.MOVE_CHARACTER, amount, direction);
    };

    return {
      movementAmount,
      movementType,
      moveCharacterDirectionally,
    };
  };
