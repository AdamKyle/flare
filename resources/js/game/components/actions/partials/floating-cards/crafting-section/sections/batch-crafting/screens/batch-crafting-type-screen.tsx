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
  const canCraftAndEnchant =
    status?.capabilities?.can_craft_and_enchant ?? false;
  const canEnchantForEvent =
    status?.capabilities?.can_enchant_for_event ?? false;
  const canAlchemy = status?.capabilities?.can_alchemy ?? false;
  const canHolyOils = status?.capabilities?.can_holy_oils ?? false;
  const canTrinketry = status?.capabilities?.can_trinketry ?? false;

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

    if (canCraftAndEnchant) {
      items.push({
        label: 'Craft and Enchant',
        value: BatchCraftingEntryType.CRAFT_AND_ENCHANT,
      });
    }

    if (canEnchantForEvent) {
      items.push({
        label: 'Enchant For Event',
        value: BatchCraftingEntryType.ENCHANT_FOR_EVENT,
      });
    }

    if (canAlchemy) {
      items.push({
        label: 'Alchemy',
        value: BatchCraftingEntryType.ALCHEMY,
      });
    }

    if (canHolyOils) {
      items.push({
        label: 'Holy Oils',
        value: BatchCraftingEntryType.HOLY_OILS,
      });
    }

    if (canTrinketry) {
      items.push({
        label: 'Trinketry',
        value: BatchCraftingEntryType.TRINKETRY,
      });
    }

    return items;
  }, [
    canCraftForEvent,
    canCraftAndEnchant,
    canEnchantForEvent,
    canAlchemy,
    canHolyOils,
    canTrinketry,
  ]);

  const handleSelect = (item: DropdownItem) => {
    if (item.value === BatchCraftingEntryType.CRAFT_FOR_EVENT) {
      navigation.navigateTo(BatchCraftingScreenNames.CRAFT_EVENT, {});

      return;
    }

    if (item.value === BatchCraftingEntryType.CRAFT_AND_ENCHANT) {
      navigation.navigateTo(
        BatchCraftingScreenNames.CRAFT_AND_ENCHANT_MODE,
        {}
      );

      return;
    }

    if (item.value === BatchCraftingEntryType.ENCHANT_FOR_EVENT) {
      navigation.navigateTo(BatchCraftingScreenNames.ENCHANT_EVENT, {});

      return;
    }

    if (item.value === BatchCraftingEntryType.ALCHEMY) {
      navigation.navigateTo(BatchCraftingScreenNames.ALCHEMY_MODE, {});

      return;
    }

    if (item.value === BatchCraftingEntryType.HOLY_OILS) {
      navigation.navigateTo(BatchCraftingScreenNames.HOLY_OILS_MODE, {});

      return;
    }

    if (item.value === BatchCraftingEntryType.TRINKETRY) {
      navigation.navigateTo(BatchCraftingScreenNames.TRINKETRY, {});

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
