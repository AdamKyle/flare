import React, { useEffect, useState } from 'react';

import { GameDataContext } from '../game-data-context';
import GameDataProviderProps from './types/game-data-provider-props';
import GameDataDefinition from '../deffinitions/game-data-definition';

const GameDataProvider = (props: GameDataProviderProps): React.ReactNode => {
  const [gameData, setGameData] = useState<GameDataDefinition | null>(null);

  const [characterId, setCharacterId] = useState<number>(0);

  useEffect(() => {
    const playerIdMeta = document.querySelector('meta[name="player"]');
    const characterIdContent = playerIdMeta?.getAttribute('content');
    if (characterIdContent) {
      setCharacterId(parseInt(characterIdContent, 10) || 0);
    }
  }, []);

  return (
    <GameDataContext.Provider value={{ gameData, setGameData, characterId }}>
      {props.children}
    </GameDataContext.Provider>
  );
};

export default GameDataProvider;
