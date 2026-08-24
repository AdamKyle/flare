import React, { ReactNode, useState } from 'react';

import CraftSetPositionSelectorProps from './types/craft-set-position-selector-props';
import { useCraftableItemsApi } from '../../crafting/api/hooks/use-craftable-items-api';

import { useGameData } from 'game-data/hooks/use-game-data';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const CraftSetPositionSelector = ({
  label,
  crafting_type,
  armour_type,
  item_type,
  selected_item,
  on_select,
}: CraftSetPositionSelectorProps): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const [optionsRequested, setOptionsRequested] = useState(false);
  const [searchText, setSearchText] = useState('');

  const {
    items,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText: setApiSearchText,
  } = useCraftableItemsApi({
    characterId,
    selectedType: optionsRequested ? crafting_type : null,
    armourType: armour_type,
    itemType: item_type,
  });

  const itemOptions: DropdownItem[] = items.map((item) => ({
    label: item.preview.name,
    value: item.id,
  }));

  const handleSearch = (value: string) => {
    setSearchText(value);
    setApiSearchText(value);
  };

  const subtype = armour_type ?? item_type ?? label;
  const legendId = `craft-set-position-${crafting_type}-${subtype}-legend`
    .toLowerCase()
    .replace(/[^a-z0-9-]/g, '-');

  const emptyMessage =
    !optionsRequested || loading
      ? 'Loading craftable items...'
      : 'No craftable items found.';

  return (
    <div className="space-y-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
      <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
        {label}
      </h4>
      <fieldset>
        <legend
          id={legendId}
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          <span className="sr-only">{label} </span>
          Item
        </legend>
        <Dropdown
          aria_labelled_by={legendId}
          items={itemOptions}
          on_select={on_select}
          pre_selected_item={selected_item ?? undefined}
          selection_placeholder="Select an item"
          searchable
          search_value={searchText}
          on_search={handleSearch}
          can_load_more={canLoadMore}
          is_loading_more={isLoadingMore}
          on_end_reached={onEndReached}
          empty_message={emptyMessage}
          search_placeholder="Search items"
          focus_selected_on_open
          on_open={() => setOptionsRequested(true)}
        />
      </fieldset>
    </div>
  );
};

export default CraftSetPositionSelector;
