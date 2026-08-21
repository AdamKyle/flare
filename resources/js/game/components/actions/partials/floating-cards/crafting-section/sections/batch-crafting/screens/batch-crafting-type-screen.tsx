import React, { ReactNode, useMemo } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingEntryType } from '../enums/batch-crafting-entry-type';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const BatchCraftingTypeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();

  const canCraftForEvent = status?.capabilities?.can_craft_for_event ?? false;

  const options = useMemo((): DropdownItem[] => {
    const items: DropdownItem[] = [
      { label: 'Craft', value: BatchCraftingEntryType.CRAFT },
    ];

    if (canCraftForEvent) {
      items.push({
        label: 'Craft For Event',
        value: BatchCraftingEntryType.CRAFT_FOR_EVENT,
      });
    }

    return items;
  }, [canCraftForEvent]);

  const handleSelect = (item: DropdownItem) => {
    if (item.value === BatchCraftingEntryType.CRAFT_FOR_EVENT) {
      navigation.navigateTo(BatchCraftingScreenNames.CRAFT_EVENT, {});

      return;
    }

    navigation.navigateTo(BatchCraftingScreenNames.MODE, {});
  };

  return (
    <div className="space-y-4">
      <fieldset>
        <legend
          id="batch-crafting-type-legend"
          className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          What do you want to craft?
        </legend>
        <Dropdown
          aria_labelled_by="batch-crafting-type-legend"
          items={options}
          on_select={handleSelect}
          selection_placeholder="Please select a batch type"
        />
      </fieldset>
    </div>
  );
};

export default BatchCraftingTypeScreen;
