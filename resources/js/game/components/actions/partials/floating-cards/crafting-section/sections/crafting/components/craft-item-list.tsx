import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CraftItemListProps from './types/craft-item-list-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const CraftItemList = ({
  items,
  selectedItem,
  loadingMore,
  handle_scroll,
  onSelect,
}: CraftItemListProps): ReactNode => {
  if (items.length === 0) {
    return (
      <p className="py-6 text-center text-sm text-gray-600 dark:text-gray-300">
        No craftable items were found.
      </p>
    );
  }

  return (
    <InfiniteScroll handle_scroll={handle_scroll} additional_css="max-h-64">
      <div className="space-y-2 pr-1">
        {items.map((item) => (
          <button
            key={item.id}
            type="button"
            onClick={() => onSelect(item)}
            aria-pressed={selectedItem?.id === item.id}
            className={clsx(
              'w-full rounded-md border p-3 text-left transition-colors',
              selectedItem?.id === item.id
                ? 'border-danube-500 bg-danube-100 dark:bg-danube-700'
                : 'border-gray-400 bg-white hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:hover:bg-gray-900'
            )}
          >
            <span className="block font-semibold">{item.name}</span>
            <span className="mt-1 block text-sm">
              Cost: {formatNumberWithCommas(item.cost)} gold
            </span>
            <span className="block text-xs text-gray-600 dark:text-gray-400">
              Type: {item.type}
            </span>
          </button>
        ))}
        {loadingMore && <InfiniteLoader />}
      </div>
    </InfiniteScroll>
  );
};

export default CraftItemList;
