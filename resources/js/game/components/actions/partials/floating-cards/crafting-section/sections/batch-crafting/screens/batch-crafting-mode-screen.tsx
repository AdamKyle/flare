import React, { ReactNode, useMemo } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MODE_SCREENS: Partial<
  Record<CraftingBatchMode, BatchCraftingScreenNames>
> = {
  [CraftingBatchMode.EXPERIENCE]: BatchCraftingScreenNames.CRAFT_EXPERIENCE,
  [CraftingBatchMode.AMOUNT]: BatchCraftingScreenNames.CRAFT_AMOUNT,
  [CraftingBatchMode.SET]: BatchCraftingScreenNames.CRAFT_SET_OUTPUT,
};

const isCraftingBatchMode = createEnumValueGuard(CraftingBatchMode);

const BatchCraftingModeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();

  const canCraftForExperience =
    status?.capabilities?.can_craft_for_experience ?? false;

  const options = useMemo((): DropdownItem[] => {
    const items: DropdownItem[] = [];

    if (canCraftForExperience) {
      items.push({
        label: 'Craft For Experience',
        value: CraftingBatchMode.EXPERIENCE,
      });
    }

    items.push({ label: 'Craft Amount', value: CraftingBatchMode.AMOUNT });
    items.push({ label: 'Craft Set', value: CraftingBatchMode.SET });

    return items;
  }, [canCraftForExperience]);

  const handleSelect = (item: DropdownItem) => {
    if (typeof item.value !== 'string' || !isCraftingBatchMode(item.value)) {
      return;
    }

    const screen = MODE_SCREENS[item.value];

    if (!screen) {
      return;
    }

    navigation.navigateTo(screen, {});
  };

  return (
    <div className="space-y-4">
      <fieldset>
        <legend
          id="batch-crafting-mode-legend"
          className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          How do you want to craft?
        </legend>
        <Dropdown
          aria_labelled_by="batch-crafting-mode-legend"
          items={options}
          on_select={handleSelect}
          selection_placeholder="Please select a craft mode"
        />
      </fieldset>
    </div>
  );
};

export default BatchCraftingModeScreen;
