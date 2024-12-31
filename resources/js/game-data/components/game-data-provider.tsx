import React, { useState } from 'react';

import { GameDataContext } from '../game-data-context';
import GameDataProviderProps from './types/game-data-provider-props';
import GameDataDefinition from '../deffinitions/game-data-definition';

const GameDataProvider = (props: GameDataProviderProps): React.ReactNode => {
  const [gameData, setGameData] = useState<GameDataDefinition | null>(null);

  return (
    <GameDataContext.Provider value={{ gameData, setGameData }}>
      {props.children}
    </GameDataContext.Provider>
  );
};

export default GameDataProvider;
