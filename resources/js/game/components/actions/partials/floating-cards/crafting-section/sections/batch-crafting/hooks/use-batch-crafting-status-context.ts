import { useContext } from 'react';

import UseBatchCraftingStatusDefinition from '../api/hooks/definitions/use-batch-crafting-status-definition';
import { BatchCraftingStatusContext } from '../providers/batch-crafting-status-provider';

export const useBatchCraftingStatusContext =
  (): UseBatchCraftingStatusDefinition => {
    const context = useContext(BatchCraftingStatusContext);

    if (context === undefined) {
      throw new Error(
        'useBatchCraftingStatusContext must be used within a BatchCraftingStatusProvider'
      );
    }

    return context;
  };
