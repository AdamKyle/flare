import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { Map } from '../event-types/map';
import UseEmitCharacterPosition from './definitions/use-emit-character-position';
import CharacterMapPosition from '../types/character-map-position';

export const useEmitCharacterPosition = (): UseEmitCharacterPosition => {
  const eventSystem = useEventSystem();

  const characterPositionEmitter = eventSystem.fetchOrCreateEventEmitter<{
    [key: string]: CharacterMapPosition;
  }>(Map.CHARACTER_POSITION);

  const [characterPosition, setCharacterPosition] =
    useState<CharacterMapPosition>({ x: 0, y: 0 });

  useEffect(() => {
    const updateCharacterPosition = (characterPosition: CharacterMapPosition) =>
      setCharacterPosition(characterPosition);

    characterPositionEmitter.on(
      Map.CHARACTER_POSITION,
      updateCharacterPosition
    );

    return () => {
      characterPositionEmitter.off(
        Map.CHARACTER_POSITION,
        updateCharacterPosition
      );
    };
  }, [characterPositionEmitter]);

  const emitCharacterPosition = (characterPosition: CharacterMapPosition) => {
    characterPositionEmitter.emit(Map.CHARACTER_POSITION, characterPosition);
  };

  return { characterPosition, emitCharacterPosition };
};
