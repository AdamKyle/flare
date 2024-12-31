import React from 'react';

import GameDataDefinition from './game-data-definition';

export default interface GameDataContextDefinition {
  gameData: GameDataDefinition | null;
  setGameData: React.Dispatch<React.SetStateAction<GameDataDefinition | null>>;
  characterId: number;
}
