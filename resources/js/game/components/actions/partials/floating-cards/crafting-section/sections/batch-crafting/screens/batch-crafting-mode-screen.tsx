import React, { ReactNode, useState } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftingBatchMode } from '../enums/crafting-batch-mode';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const BATCH_MODE_OPTIONS: DropdownItem[] = [
  { label: 'Craft Amount', value: CraftingBatchMode.AMOUNT },
];

const BatchCraftingModeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const [selected, setSelected] = useState<DropdownItem>(BATCH_MODE_OPTIONS[0]);

  const handleContinue = () => {
    navigation.navigateTo(BatchCraftingScreenNames.CRAFT_AMOUNT, {});
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Craft Mode</h3>
      <fieldset>
        <legend
          id="batch-crafting-mode-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Craft Mode
        </legend>
        <Dropdown
          aria_labelled_by="batch-crafting-mode-legend"
          items={BATCH_MODE_OPTIONS}
          on_select={setSelected}
          pre_selected_item={selected}
          selection_placeholder="Select a mode"
        />
      </fieldset>
      <Button
        label="Continue"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        on_click={handleContinue}
      />
    </div>
  );
};

export default BatchCraftingModeScreen;
