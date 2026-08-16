import React, { ReactNode, useCallback, useEffect, useRef } from 'react';

import BatchCraftingScreenManager from './component-mapping/batch-crafting-screen-manager';
import { batchCraftingScreenRegistry } from './component-mapping/batch-crafting-screen-registry';
import { BatchCraftingScreenNames } from './enums/batch-crafting-screen-names';
import { CraftingTypes } from '../../enums/crafting-types';
import CraftingSectionScreenProps from '../../types/crafting-section-screen-props';

import FloatingCardScreenStack from 'ui/floating-card-screen-stack/floating-card-screen-stack';

const BatchCraftingStack = ({
  setActiveCraftingType,
  registerBackHandler,
}: CraftingSectionScreenProps): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const hasInitialized = useRef(false);

  useEffect(() => {
    if (hasInitialized.current) {
      return;
    }

    hasInitialized.current = true;
    navigation.resetTo(BatchCraftingScreenNames.ENTRY, {});
  }, [navigation]);

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

  return (
    <FloatingCardScreenStack label="Batch Crafting">
      <BatchCraftingScreenManager.ScreenHost />
    </FloatingCardScreenStack>
  );
};

const BatchCraftingSection = ({
  setActiveCraftingType,
  registerBackHandler,
}: CraftingSectionScreenProps): ReactNode => {
  return (
    <BatchCraftingScreenManager.ScreenManagerProvider
      registry={batchCraftingScreenRegistry}
    >
      <BatchCraftingStack
        setActiveCraftingType={setActiveCraftingType}
        registerBackHandler={registerBackHandler}
      />
    </BatchCraftingScreenManager.ScreenManagerProvider>
  );
};

export default BatchCraftingSection;
