import React, { ReactNode, useCallback, useEffect, useState } from 'react';

import BatchCraftingScreenManager from './component-mapping/batch-crafting-screen-manager';
import { batchCraftingScreenRegistry } from './component-mapping/batch-crafting-screen-registry';
import BatchCraftingEntry from './components/batch-crafting-entry';
import { BatchCraftingScreenNames } from './enums/batch-crafting-screen-names';
import BatchCraftingStatusProvider from './providers/batch-crafting-status-provider';
import { CraftingTypes } from '../../enums/crafting-types';
import CraftingSectionScreenProps from '../../types/crafting-section-screen-props';

import FloatingCardScreenStack from 'ui/floating-card-screen-stack/floating-card-screen-stack';

const BatchCraftingStack = ({
  setActiveCraftingType,
  registerBackHandler,
}: CraftingSectionScreenProps): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const [isInitialized, setIsInitialized] = useState(false);

  const handleEntryReady = useCallback(
    (screen: BatchCraftingScreenNames) => {
      navigation.resetTo(screen, {});
      setIsInitialized(true);
    },
    [navigation]
  );

  const handleBack = useCallback(() => {
    if (navigation.stackDepth <= 1) {
      setActiveCraftingType(CraftingTypes.HOME);

      return;
    }

    navigation.pop();
  }, [navigation, setActiveCraftingType]);

  useEffect(() => {
    registerBackHandler?.(handleBack);

    return () => registerBackHandler?.(null);
  }, [handleBack, registerBackHandler]);

  const renderBatchCraftingContent = () => {
    if (!isInitialized) {
      return <BatchCraftingEntry on_ready={handleEntryReady} />;
    }

    return <BatchCraftingScreenManager.ScreenHost />;
  };

  return (
    <FloatingCardScreenStack label="Batch Crafting">
      {renderBatchCraftingContent()}
    </FloatingCardScreenStack>
  );
};

const BatchCraftingSection = ({
  setActiveCraftingType,
  registerBackHandler,
}: CraftingSectionScreenProps): ReactNode => {
  return (
    <BatchCraftingStatusProvider>
      <BatchCraftingScreenManager.ScreenManagerProvider
        registry={batchCraftingScreenRegistry}
      >
        <BatchCraftingStack
          setActiveCraftingType={setActiveCraftingType}
          registerBackHandler={registerBackHandler}
        />
      </BatchCraftingScreenManager.ScreenManagerProvider>
    </BatchCraftingStatusProvider>
  );
};

export default BatchCraftingSection;
