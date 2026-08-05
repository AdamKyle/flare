import React, { ReactNode } from 'react';

import TrinketSelectionProps from './types/trinket-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const TrinketSelection = ({
  items,
  selectedItemId,
  loading,
  isLoadingMore,
  canLoadMore,
  searchText,
  onSearch,
  onEndReached,
  onSelect,
}: TrinketSelectionProps): ReactNode => {
  const preSelectedItem = items.find(
    (option) => option.value === selectedItemId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label id="trinketry-item-label" className="mb-2 block font-semibold">
        Trinket
      </label>

      <Dropdown
        aria_labelled_by="trinketry-item-label"
        items={items}
        selection_placeholder={
          loading ? 'Loading Trinkets…' : 'Select a Trinket'
        }
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
        searchable
        search_value={searchText}
        on_search={onSearch}
        can_load_more={canLoadMore}
        is_loading_more={isLoadingMore}
        on_end_reached={onEndReached}
        empty_message="No Trinkets are available."
        disabled={loading}
      />
    </div>
  );
};

export default TrinketSelection;
