import React, { ReactNode, useMemo } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { AlchemyBatchMode } from '../enums/alchemy-batch-mode';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MODE_SCREENS: Partial<
  Record<AlchemyBatchMode, BatchCraftingScreenNames>
> = {
  [AlchemyBatchMode.AMOUNT]: BatchCraftingScreenNames.ALCHEMY_AMOUNT,
  [AlchemyBatchMode.EXPERIENCE]: BatchCraftingScreenNames.ALCHEMY_EXPERIENCE,
};

const isAlchemyBatchMode = createEnumValueGuard(AlchemyBatchMode);

const AlchemyModeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();

  const canAlchemyForExperience =
    status?.capabilities?.can_alchemy_for_experience ?? false;

  const options = useMemo((): DropdownItem[] => {
    const items: DropdownItem[] = [
      { label: 'Alchemy Amount', value: AlchemyBatchMode.AMOUNT },
    ];

    if (canAlchemyForExperience) {
      items.push({
        label: 'Alchemy For Experience',
        value: AlchemyBatchMode.EXPERIENCE,
      });
    }

    return items;
  }, [canAlchemyForExperience]);

  const handleSelect = (item: DropdownItem) => {
    if (typeof item.value !== 'string' || !isAlchemyBatchMode(item.value)) {
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
          id="alchemy-mode-legend"
          className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          How do you want to perform Alchemy?
        </legend>
        <Dropdown
          aria_labelled_by="alchemy-mode-legend"
          items={options}
          on_select={handleSelect}
          selection_placeholder="Please select a mode"
        />
      </fieldset>
    </div>
  );
};

export default AlchemyModeScreen;
