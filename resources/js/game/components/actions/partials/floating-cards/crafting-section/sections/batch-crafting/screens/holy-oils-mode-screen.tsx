import React, { ReactNode } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { HolyOilsBatchMode } from '../enums/holy-oils-batch-mode';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MODE_SCREENS: Record<HolyOilsBatchMode, BatchCraftingScreenNames> = {
  [HolyOilsBatchMode.SELECTED_ITEMS]:
    BatchCraftingScreenNames.HOLY_OILS_SELECTED_ITEMS,
  [HolyOilsBatchMode.INVENTORY_SET]: BatchCraftingScreenNames.HOLY_OILS_SET,
};

const OPTIONS: DropdownItem[] = [
  { label: 'Selected Items', value: HolyOilsBatchMode.SELECTED_ITEMS },
  { label: 'Inventory Set', value: HolyOilsBatchMode.INVENTORY_SET },
];

const isHolyOilsBatchMode = createEnumValueGuard(HolyOilsBatchMode);

const HolyOilsModeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const handleSelect = (item: DropdownItem) => {
    if (typeof item.value !== 'string' || !isHolyOilsBatchMode(item.value)) {
      return;
    }

    navigation.navigateTo(MODE_SCREENS[item.value], {});
  };

  return (
    <div className="space-y-4">
      <fieldset>
        <legend
          id="holy-oils-mode-legend"
          className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          How do you want to apply Holy Oils?
        </legend>
        <Dropdown
          aria_labelled_by="holy-oils-mode-legend"
          items={OPTIONS}
          on_select={handleSelect}
          selection_placeholder="Please select a mode"
        />
      </fieldset>
    </div>
  );
};

export default HolyOilsModeScreen;
