import { isEmpty } from 'lodash';
import React, { ReactNode, useRef, useState } from 'react';

import GenericItem from './generic-item';
import GenericItemListProps from './types/generic-item-list-props';
import { ItemSelectedType } from './types/item-selection-type';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const GenericItemList = ({
  items,
  is_quest_items,
  on_scroll_to_end,
  on_click,
  on_selection_change,
  is_selection_disabled,
}: GenericItemListProps): ReactNode => {
  const [selectedItems, setSelectedItems] = useState<ItemSelectedType>([]);
  const [excludedIds, setExcludedIds] = useState<Set<number>>(new Set());
  const selectAllRef = useRef<HTMLInputElement | null>(null);

  const handleClick = (
    item: EquippableItemWithBase | BaseQuestItemDefinition
  ) => {
    if (!on_click) {
      return;
    }

    return on_click(item.id);
  };

  const handleSelectAllChange = (checked: boolean) => {
    if (checked) {
      setSelectedItems('all');
      setExcludedIds(new Set());

      if (on_selection_change) {
        on_selection_change({ mode: 'all_except', exclude: [] });
      }

      return;
    }

    setSelectedItems([]);
    setExcludedIds(new Set());

    if (on_selection_change) {
      on_selection_change({ mode: 'include', ids: [] });
    }
  };

  const handleSelectItem = (itemId: number, checked: boolean) => {
    if (selectedItems === 'all') {
      setExcludedIds((prev) => {
        const next = new Set(prev);

        if (checked) {
          next.delete(itemId);
        } else {
          next.add(itemId);
        }

        if (on_selection_change) {
          on_selection_change({
            mode: 'all_except',
            exclude: Array.from(next),
          });
        }

        return next;
      });

      return;
    }

    setSelectedItems((prev) => {
      const next = new Set(prev as number[]);

      if (checked) {
        next.add(itemId);
      } else {
        next.delete(itemId);
      }

      const nextArray = Array.from(next);

      if (on_selection_change) {
        on_selection_change({ mode: 'include', ids: nextArray });
      }

      return nextArray;
    });
  };

  const isItemSelected = (id: number) => {
    if (selectedItems === 'all') {
      return !excludedIds.has(id);
    }

    return (selectedItems as number[]).includes(id);
  };

  const renderSelectAllHeader = () => {
    if (is_quest_items) {
      return null;
    }

    const visibleIds = items.map((i) => i.id);

    const visibleSelectedCount = visibleIds.reduce((acc, id) => {
      if (isItemSelected(id)) {
        return acc + 1;
      }

      return acc;
    }, 0);

    const allVisibleChecked =
      visibleIds.length > 0 && visibleSelectedCount === visibleIds.length;

    const noneVisibleChecked = visibleSelectedCount === 0;

    const isIndeterminate = !allVisibleChecked && !noneVisibleChecked;

    const headerChecked = selectedItems === 'all' && excludedIds.size === 0;

    const selectedCount =
      selectedItems === 'all'
        ? Math.max(0, items.length - excludedIds.size)
        : (selectedItems as number[]).length;

    if (selectAllRef.current) {
      selectAllRef.current.indeterminate = isIndeterminate;
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
              dark:focus-visible:ring-danube-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white
              dark:focus-visible:ring-offset-gray-900"
              checked={headerChecked}
              onChange={(e) => handleSelectAllChange(e.target.checked)}
              disabled={is_selection_disabled}
            />
            <span className="font-medium">Select all (visible)</span>
          </label>

          <output
            role="status"
            aria-live="polite"
            className="text-sm text-gray-600 dark:text-gray-400"
          >
            {selectedCount} selected
          </output>
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
          key={item.id}
          item={item}
          on_click={handleClick}
          is_selected={isItemSelected(item.id)}
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
