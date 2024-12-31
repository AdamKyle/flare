import { useContext } from 'react';

import GameDataContextDefinition from '../deffinitions/game-data-context-definition';
import { GameDataContext } from '../game-data-context';

export const useGameData = (): GameDataContextDefinition => {
  const context = useContext(GameDataContext);

  if (!context) {
    throw new Error('useGameData must be used within an GameDataProvider');
  }

  return context;
};
