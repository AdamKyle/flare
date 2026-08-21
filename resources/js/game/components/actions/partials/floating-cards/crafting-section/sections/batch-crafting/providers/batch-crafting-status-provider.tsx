import React, { createContext, ReactNode } from 'react';

import BatchCraftingStatusProviderProps from './types/batch-crafting-status-provider-props';
import UseBatchCraftingStatusDefinition from '../api/hooks/definitions/use-batch-crafting-status-definition';
import { useBatchCraftingStatus } from '../api/hooks/use-batch-crafting-status';

import { useGameData } from 'game-data/hooks/use-game-data';

export const BatchCraftingStatusContext = createContext<
  UseBatchCraftingStatusDefinition | undefined
>(undefined);

const BatchCraftingStatusProvider = ({
  children,
}: BatchCraftingStatusProviderProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const userId = gameData?.character?.user_id ?? 0;

  const { status, loading, error } = useBatchCraftingStatus({
    characterId,
    userId,
  });

  return (
    <BatchCraftingStatusContext.Provider value={{ status, loading, error }}>
      {children}
    </BatchCraftingStatusContext.Provider>
  );
};

export default BatchCraftingStatusProvider;
