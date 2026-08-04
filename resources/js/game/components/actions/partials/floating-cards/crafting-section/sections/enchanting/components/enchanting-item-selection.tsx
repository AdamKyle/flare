import React, { ReactNode, useMemo } from 'react';

import { buildEnchantingItemOptions } from '../utils/build-enchanting-item-options';
import EnchantingItemSelectionProps from './types/enchanting-item-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const EnchantingItemSelection = ({
  regularItems,
  eventItems,
  source,
  selectedSlotId,
  onSelect,
}: EnchantingItemSelectionProps): ReactNode => {
  const options = useMemo(
    () => buildEnchantingItemOptions(source, regularItems, eventItems),
    [source, regularItems, eventItems]
  );

  const preSelectedItem = options.find(
    (option) => option.value === selectedSlotId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label id="enchanting-item-label" className="mb-2 block font-semibold">
        Item to enchant
      </label>

      <Dropdown
        aria_labelled_by="enchanting-item-label"
        items={options}
        selection_placeholder="Select an item"
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
      />
    </div>
  );
};

export default EnchantingItemSelection;
