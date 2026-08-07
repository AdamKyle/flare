import React, { ReactNode } from 'react';

import EnchantingItemSelectionProps from './types/enchanting-item-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const EnchantingItemSelection = ({
  items,
  selectedSlotId,
  selectedItemName,
  loading,
  isLoadingMore,
  canLoadMore,
  searchText,
  onSearch,
  onEndReached,
  onSelect,
}: EnchantingItemSelectionProps): ReactNode => {
  const loadedSelectedOption: DropdownItem | undefined = items.find(
    (option) => option.value === selectedSlotId
  );

  const selectedOption: DropdownItem | undefined =
    loadedSelectedOption ??
    (selectedSlotId !== null && selectedItemName !== null
      ? { value: selectedSlotId, label: selectedItemName }
      : undefined);

  const isSelectedOptionLoaded = loadedSelectedOption !== undefined;

  const dropdownItems: DropdownItem[] =
    selectedOption && !isSelectedOptionLoaded
      ? [selectedOption, ...items]
      : items;

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value), String(option.label));
  };

  return (
    <div>
      <label id="enchanting-item-label" className="mb-2 block font-semibold">
        Item to enchant
      </label>

      <Dropdown
        key={selectedSlotId ?? 'none'}
        aria_labelled_by="enchanting-item-label"
        items={dropdownItems}
        selection_placeholder={loading ? 'Loading items…' : 'Select an item'}
        pre_selected_item={selectedOption}
        on_select={handleSelect}
        searchable
        search_value={searchText}
        on_search={onSearch}
        can_load_more={canLoadMore}
        is_loading_more={isLoadingMore}
        on_end_reached={onEndReached}
        empty_message="No items are available to enchant."
        disabled={loading}
      />
    </div>
  );
};

export default EnchantingItemSelection;
