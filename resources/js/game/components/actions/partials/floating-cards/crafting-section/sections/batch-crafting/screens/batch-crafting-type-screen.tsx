import React, { ReactNode, useState } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { BatchCraftingType } from '../enums/batch-crafting-type';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const BATCH_TYPE_OPTIONS: DropdownItem[] = [
  { label: 'Craft', value: BatchCraftingType.CRAFT },
];

const BatchCraftingTypeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const [selected, setSelected] = useState<DropdownItem>(BATCH_TYPE_OPTIONS[0]);

  const handleContinue = () => {
    navigation.navigateTo(BatchCraftingScreenNames.MODE, {});
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Batch Type</h3>
      <fieldset>
        <legend
          id="batch-crafting-type-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Batch Type
        </legend>
        <Dropdown
          aria_labelled_by="batch-crafting-type-legend"
          items={BATCH_TYPE_OPTIONS}
          on_select={setSelected}
          pre_selected_item={selected}
          selection_placeholder="Select a batch type"
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

export default BatchCraftingTypeScreen;
