import React, { ReactNode, useMemo } from 'react';

import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { CraftAndEnchantBatchMode } from '../enums/craft-and-enchant-batch-mode';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MODE_SCREENS: Partial<
  Record<CraftAndEnchantBatchMode, BatchCraftingScreenNames>
> = {
  [CraftAndEnchantBatchMode.AMOUNT]:
    BatchCraftingScreenNames.CRAFT_AND_ENCHANT_AMOUNT_OUTPUT,
  [CraftAndEnchantBatchMode.EXPERIENCE]:
    BatchCraftingScreenNames.CRAFT_AND_ENCHANT_EXPERIENCE,
  [CraftAndEnchantBatchMode.SET]:
    BatchCraftingScreenNames.CRAFT_AND_ENCHANT_SET_OUTPUT,
};

const isCraftAndEnchantBatchMode = createEnumValueGuard(
  CraftAndEnchantBatchMode
);

const CraftAndEnchantModeScreen = (): ReactNode => {
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();

  const canCraftAndEnchantForExperience =
    status?.capabilities?.can_craft_and_enchant_for_experience ?? false;

  const options = useMemo((): DropdownItem[] => {
    const items: DropdownItem[] = [
      {
        label: 'Craft and Enchant Amount',
        value: CraftAndEnchantBatchMode.AMOUNT,
      },
    ];

    if (canCraftAndEnchantForExperience) {
      items.push({
        label: 'Craft and Enchant For Experience',
        value: CraftAndEnchantBatchMode.EXPERIENCE,
      });
    }

    items.push({
      label: 'Craft and Enchant Set',
      value: CraftAndEnchantBatchMode.SET,
    });

    return items;
  }, [canCraftAndEnchantForExperience]);

  const handleSelect = (item: DropdownItem) => {
    if (
      typeof item.value !== 'string' ||
      !isCraftAndEnchantBatchMode(item.value)
    ) {
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
          id="craft-and-enchant-mode-legend"
          className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100"
        >
          How do you want to craft and enchant?
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-mode-legend"
          items={options}
          on_select={handleSelect}
          selection_placeholder="Please select a mode"
        />
      </fieldset>
    </div>
  );
};

export default CraftAndEnchantModeScreen;
