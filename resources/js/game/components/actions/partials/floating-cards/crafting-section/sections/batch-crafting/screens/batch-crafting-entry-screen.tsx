import React, { ReactNode, useEffect } from 'react';

import { useBatchCraftingStatus } from '../api/hooks/use-batch-crafting-status';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const BatchCraftingEntryScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const userId = gameData?.character?.user_id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status, loading, error } = useBatchCraftingStatus({
    characterId,
    userId,
  });

  useEffect(() => {
    if (characterId <= 0 || loading || error !== null || status === null) {
      return;
    }

    if (status.active || status.is_visible) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});

      return;
    }

    if (status.show_info) {
      navigation.resetTo(BatchCraftingScreenNames.INTRODUCTION, {});

      return;
    }

    navigation.resetTo(BatchCraftingScreenNames.TYPE, {});
  }, [characterId, loading, error, status, navigation]);

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  }

  return <p>Loading Batch Crafting...</p>;
};

export default BatchCraftingEntryScreen;
