import { isEmpty } from 'lodash';
import React, { ReactNode, useRef, useState } from 'react';

import GenericItem from './generic-item';
import GenericItemListProps from './types/generic-item-list-props';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { ItemSelectedType } from '../../character-inventory/types/item-selected-type';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const GenericItemList = ({
  items,
  is_quest_items,
  on_scroll_to_end,
  on_click,
  on_selection_change,
  is_selection_disabled,
  use_item_id,
}: GenericItemListProps): ReactNode => {
  const [selection, setSelection] = useState<ItemSelectedType>({
    mode: 'include',
    ids: [],
    exclude: [],
  });

  const selectAllRef = useRef<HTMLInputElement | null>(null);

  const handleClick = (
    item: EquippableItemWithBase | BaseQuestItemDefinition
  ) => {
    if (!on_click) {
      return;
    }

    if (use_item_id) {
      return on_click(item.item_id);
    }

    return on_click(item.slot_id);
  };

  const handleSelectAllChange = (checked: boolean) => {
    if (checked) {
      const next: ItemSelectedType = { mode: 'all_except', exclude: [] };

      setSelection(next);
      on_selection_change?.(next);

      return;
    }

    const next: ItemSelectedType = { mode: 'include', ids: [] };

    setSelection(next);
    on_selection_change?.(next);
  };

  const handleSelectItem = (itemId: number, checked: boolean) => {
    setSelection((previous) => {
      const isAllExcept = previous.mode === 'all_except';

      const ids = new Set(
        isAllExcept ? (previous.exclude ?? []) : (previous.ids ?? [])
      );

      if (checked && isAllExcept) {
        ids.delete(itemId);
      } else if (checked && !isAllExcept) {
        ids.add(itemId);
      } else if (!checked && isAllExcept) {
        ids.add(itemId);
      } else {
        ids.delete(itemId);
      }

      const next: ItemSelectedType = isAllExcept
        ? { mode: 'all_except', exclude: Array.from(ids) }
        : { mode: 'include', ids: Array.from(ids) };

      on_selection_change?.(next);

      return next;
    });
  };

  const isItemSelected = (id: number) => {
    if (selection.mode === 'all_except') {
      const excluded = selection.exclude ?? [];

      return !excluded.includes(id);
    }

    const included = selection.ids ?? [];

    return included.includes(id);
  };

  const renderSelectAllHeader = () => {
    if (is_quest_items) {
      return null;
    }

    const visibleSlotIds = items.map((item) => item.slot_id);

    const hasAllSelected =
      visibleSlotIds.length > 0 &&
      visibleSlotIds.every((slotId) => isItemSelected(slotId));

    const hasAnySelected = visibleSlotIds.some((slotId) =>
      isItemSelected(slotId)
    );

    const isSelectAllIndeterminate = hasAnySelected && !hasAllSelected;

    if (selectAllRef.current) {
      selectAllRef.current.indeterminate = isSelectAllIndeterminate;
    }

    return (
      <div
        className="sticky top-0 z-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-b
      border-gray-200 dark:border-gray-700 px-2 py-2 rounded"
      >
        <div className="flex items-center justify-between">
          <label
            htmlFor="select-all-visible"
            className="inline-flex items-center gap-2 text-gray-900 dark:text-gray-100"
          >
            <input
              id="select-all-visible"
              ref={selectAllRef}
              type="checkbox"
              className="h-5 w-5 rounded-md border-2 border-gray-700 dark:border-gray-300 accent-danube-600
              dark:accent-danube-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danube-500
              dark:accent-danube-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white
              dark:focus-visible:ring-offset-gray-900"
              checked={hasAllSelected}
              onChange={(e) => handleSelectAllChange(e.target.checked)}
              disabled={is_selection_disabled}
            />
            <span className="font-medium">Select all (visible)</span>
          </label>
        </div>
      </div>
    );
  };

  const renderBackPackItems = () => {
    if (isEmpty(items) && is_quest_items) {
      return (
        <div className="text-center py-4">
          You have no quest items. Quest items are obtained by visiting
          locations and completing quests. Some items are used in subsequent
          quests while others have special effects that can unlock additional
          game content.
        </div>
      );
    }

    if (isEmpty(items) && !is_quest_items) {
      return (
        <div className="text-center py-4">
          You have nothing in your inventory that you can equip. Either use the
          shop, craft or fight monsters to get some items. You can also checkout
          the market board for items as well that other players might be
          selling!
        </div>
      );
    }

    return items.map(
      (item: EquippableItemWithBase | BaseQuestItemDefinition) => (
        <GenericItem
          key={item.slot_id}
          item={item}
          on_click={handleClick}
          is_selected={isItemSelected(item.slot_id)}
          on_item_selected={handleSelectItem}
          is_selection_disabled={is_selection_disabled}
        />
      )
    );
  };

  return (
    <div className="w-full h-full text-gray-800 dark:text-gray-200">
      <InfiniteScroll handle_scroll={on_scroll_to_end} additional_css={'my-2'}>
        {renderSelectAllHeader()}
        <h2 id="inventory-heading" className="sr-only">
          Inventory items
        </h2>
        <div role="group" aria-labelledby="inventory-heading">
          {renderBackPackItems()}
        </div>
      </InfiniteScroll>
    </div>
  );
};

export default GenericItemList;
