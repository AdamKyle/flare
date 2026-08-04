import React, { ReactNode } from 'react';

import CraftItemList from './craft-item-list';
import CraftableItemPickerProps from './types/craftable-item-picker-props';

import Input from 'ui/input/input';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const CraftableItemPicker = ({
  searchInput,
  items,
  selectedItem,
  loading,
  loadingMore,
  onSearch,
  onScroll,
  onSelect,
}: CraftableItemPickerProps): ReactNode => {
  const renderCraftItems = () => {
    if (loading) {
      return (
        <IndeterminateProgressBar
          label="Loading items..."
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    return (
      <CraftItemList
        items={items}
        selectedItem={selectedItem}
        loadingMore={loadingMore}
        handle_scroll={onScroll}
        onSelect={onSelect}
      />
    );
  };

  return (
    <>
      <fieldset>
        <p className="prose dark:prose-invert my-4">
          You can search for items you want to craft or scroll the list below
          and select an item to craft. Once you select the item the craft item
          button will appear.
        </p>
        <Input
          value={searchInput}
          on_change={onSearch}
          place_holder="Search craftable items"
          clearable
        />
      </fieldset>
      {renderCraftItems()}
    </>
  );
};

export default CraftableItemPicker;
