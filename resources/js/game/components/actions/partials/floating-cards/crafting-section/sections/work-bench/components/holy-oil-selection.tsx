import React, { ReactNode } from 'react';

import HolyOilSelectionProps from './types/holy-oil-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const HolyOilSelection = ({
  items,
  selectedSlotId,
  loading,
  isLoadingMore,
  canLoadMore,
  searchText,
  onSearch,
  onEndReached,
  onSelect,
}: HolyOilSelectionProps): ReactNode => {
  const preSelectedItem = items.find(
    (option) => option.value === selectedSlotId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label
        id="work-bench-holy-oil-label"
        className="mb-2 block font-semibold"
      >
        Holy Oil
      </label>

      <Dropdown
        aria_labelled_by="work-bench-holy-oil-label"
        items={items}
        force_clear={selectedSlotId === null}
        selection_placeholder={
          loading ? 'Loading Holy Oils…' : 'Select a Holy Oil'
        }
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
        searchable
        search_value={searchText}
        on_search={onSearch}
        can_load_more={canLoadMore}
        is_loading_more={isLoadingMore}
        on_end_reached={onEndReached}
        empty_message="No Holy Oils are available."
        disabled={loading}
      />
    </div>
  );
};

export default HolyOilSelection;
