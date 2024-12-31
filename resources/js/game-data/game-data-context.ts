import { createContext } from 'react';

import GameDataContextDefinition from './deffinitions/game-data-context-definition';

export const GameDataContext = createContext<GameDataContextDefinition | null>(
  null
);
