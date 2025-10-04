import React from 'react';

import GameDataDefinition from './game-data-definition';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface GameDataContextDefinition {
  gameData: GameDataDefinition | null;
  setGameData: React.Dispatch<React.SetStateAction<GameDataDefinition | null>>;
  updateCharacter: (character: Partial<CharacterSheetDefinition>) => void;
  characterId: number;
}
