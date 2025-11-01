import React, { useEffect, useMemo, useState } from 'react';

import { GameDataContext } from '../game-data-context';
import GameDataProviderProps from './types/game-data-provider-props';
import CharacterSheetDefinition from '../api-data-definitions/character/character-sheet-definition';
import GameDataDefinition from '../deffinitions/game-data-definition';

import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';
import UseMonsterUpdateStreamResponse from 'game-data/hooks/definitions/use-monster-update-stream-response';
import useCharacterUpdates from 'game-data/hooks/use-character-updates';
import useMonsterUpdates from 'game-data/hooks/use-monster-updates';

const GameDataProvider = (props: GameDataProviderProps) => {
  const [gameData, setGameData] = useState<GameDataDefinition | null>(null);
  const [characterId, setCharacterId] = useState<number>(0);

  useEffect(() => {
    const playerIdMeta = document.querySelector('meta[name="player"]');
    const characterIdContent = playerIdMeta?.getAttribute('content');

    if (characterIdContent) {
      setCharacterId(parseInt(characterIdContent, 10) || 0);
    }
  }, []);

  const updateCharacter = (
    character: Partial<CharacterSheetDefinition>
  ): void => {
    setGameData((prev): GameDataDefinition | null => {
      if (!prev || !prev.character) {
        return prev;
      }

      const nextCharacter: CharacterSheetDefinition = {
        ...prev.character,
        ...character,
      };

      return { ...prev, character: nextCharacter };
    });
  };

  const handleOnMonsterListUpdate = (
    monsterList: UseMonsterUpdateStreamResponse
  ) => {
    setGameData((prev): GameDataDefinition | null => {
      if (!prev || !prev.monsters) {
        return prev;
      }

      return {
        ...prev,
        monsters: monsterList.monsters,
      };
    });
  };

  const handleOnCharacterUpdate = (
    character: UseCharterUpdateStreamResponse
  ) => {
    setGameData((prev): GameDataDefinition | null => {
      if (!prev || !prev.monsters) {
        return prev;
      }

      return {
        ...prev,
        character: character.character,
      };
    });
  };

  const userIdForWire = useMemo(() => {
    const userId = gameData?.character?.user_id;

    if (!userId) {
      return 0;
    }

    return userId;
  }, [gameData?.character]);

  const {
    listening: monsterListening,
    start: startMonsterUpdates,
    renderWire: renderMonsterUpdatesWire,
  } = useMonsterUpdates({
    userId: userIdForWire,
    onEvent: handleOnMonsterListUpdate,
  });

  const {
    listening: characterUpdatesListening,
    start: startCharacterUpdates,
    renderWire: renderCharacterUpdateWire,
  } = useCharacterUpdates({
    userId: userIdForWire,
    onEvent: handleOnCharacterUpdate,
  });

  if (!characterUpdatesListening) {
    startCharacterUpdates();
  }

  const listenForMonsterUpdates = () => {
    if (!monsterListening) {
      startMonsterUpdates();
    }
  };

  return (
    <GameDataContext.Provider
      value={{
        gameData,
        setGameData,
        characterId,
        updateCharacter,
        listenForMonsterUpdates,
      }}
    >
      {renderMonsterUpdatesWire()}
      {renderCharacterUpdateWire()}
      {props.children}
    </GameDataContext.Provider>
  );
};

export default GameDataProvider;
