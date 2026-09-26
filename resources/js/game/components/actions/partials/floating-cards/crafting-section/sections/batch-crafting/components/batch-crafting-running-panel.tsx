import React, { ReactNode } from 'react';

import BatchCraftingStatusPanel from './batch-crafting-status-panel';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';

const BatchCraftingRunningPanel = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const handleDismissed = (): void => {
    navigation.resetTo(BatchCraftingScreenNames.TYPE, {});
  };

  return <BatchCraftingStatusPanel on_dismissed={handleDismissed} />;
};

export default BatchCraftingRunningPanel;
