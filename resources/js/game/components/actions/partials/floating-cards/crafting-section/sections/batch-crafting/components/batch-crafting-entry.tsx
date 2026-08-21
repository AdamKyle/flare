import React, { ReactNode, useEffect, useRef } from 'react';

import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import BatchCraftingEntryProps from './types/batch-crafting-entry-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const BatchCraftingEntry = ({
  on_ready,
}: BatchCraftingEntryProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const { status, loading, error } = useBatchCraftingStatusContext();
  const hasResolved = useRef(false);

  useEffect(() => {
    if (
      hasResolved.current ||
      characterId <= 0 ||
      loading ||
      error !== null ||
      status === null
    ) {
      return;
    }

    hasResolved.current = true;

    if (status.active || status.is_visible) {
      on_ready(BatchCraftingScreenNames.RUNNING);

      return;
    }

    if (status.show_info) {
      on_ready(BatchCraftingScreenNames.INTRODUCTION);

      return;
    }

    on_ready(BatchCraftingScreenNames.TYPE);
  }, [characterId, loading, error, status, on_ready]);

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  }

  return (
    <p role="status" aria-live="polite">
      Loading Batch Crafting...
    </p>
  );
};

export default BatchCraftingEntry;
