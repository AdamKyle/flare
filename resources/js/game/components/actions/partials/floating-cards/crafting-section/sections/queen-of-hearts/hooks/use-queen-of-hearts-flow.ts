import { useState } from 'react';

import UseQueenOfHeartsFlowDefinition from './definitions/use-queen-of-hearts-flow-definition';
import { useQueenOfHeartsApi } from '../api/hooks/use-queen-of-hearts-api';
import { QueenAction } from '../enums/queen-action';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useQueenOfHeartsFlow = (): UseQueenOfHeartsFlowDefinition => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;

  const { data, loading, error, replaceData } = useQueenOfHeartsApi({
    characterId,
  });

  const [action, setAction] = useState<QueenAction | null>(null);

  const hasData = data !== null;

  const selectAction = (nextAction: QueenAction): void => {
    setAction(nextAction);
  };

  const resetAction = (): void => {
    setAction(null);
  };

  return {
    characterId,
    data,
    loading,
    error,
    action,
    hasData,
    replaceData,
    selectAction,
    resetAction,
  };
};
