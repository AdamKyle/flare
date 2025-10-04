import React, { useEffect, useState } from 'react';

import { GameDataContext } from '../game-data-context';
import GameDataProviderProps from './types/game-data-provider-props';
import CharacterSheetDefinition from '../api-data-definitions/character/character-sheet-definition';
import GameDataDefinition from '../deffinitions/game-data-definition';

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

  return (
    <GameDataContext.Provider
      value={{ gameData, setGameData, characterId, updateCharacter }}
    >
      {props.children}
    </GameDataContext.Provider>
  );
};

export default GameDataProvider;
