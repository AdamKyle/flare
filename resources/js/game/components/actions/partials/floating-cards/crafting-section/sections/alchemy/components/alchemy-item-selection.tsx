import React, { ReactNode } from 'react';

import AlchemyItemSelectionProps from './types/alchemy-item-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const AlchemyItemSelection = ({
  items,
  selectedItemId,
  loading,
  isLoadingMore,
  canLoadMore,
  searchText,
  onSearch,
  onEndReached,
  onSelect,
}: AlchemyItemSelectionProps): ReactNode => {
  const preSelectedItem = items.find(
    (option) => option.value === selectedItemId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label id="alchemy-item-label" className="mb-2 block font-semibold">
        Alchemy item
      </label>

      <Dropdown
        aria_labelled_by="alchemy-item-label"
        items={items}
        on_select={handleSelect}
        selection_placeholder={
          loading ? 'Loading items…' : 'Select an Alchemy item'
        }
        pre_selected_item={preSelectedItem}
        searchable
        search_value={searchText}
        on_search={onSearch}
        can_load_more={canLoadMore}
        is_loading_more={isLoadingMore}
        on_end_reached={onEndReached}
        empty_message="No Alchemy items are available."
        disabled={loading}
      />
    </div>
  );
};

export default AlchemyItemSelection;
