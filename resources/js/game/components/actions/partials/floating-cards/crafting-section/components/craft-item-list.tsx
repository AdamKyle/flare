import clsx from 'clsx';
import React from 'react';

import CraftableItemDefinition from '../api/definitions/craftable-item-definition';

import { formatNumberWithCommas } from 'game-utils/format-number';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

interface CraftItemListProps {
  items: CraftableItemDefinition[];
  selectedItem: CraftableItemDefinition | null;
  loadingMore: boolean;
  onScroll: (event: React.UIEvent<HTMLDivElement>) => void;
  onSelect: (item: CraftableItemDefinition) => void;
}

const CraftItemList = ({
  items,
  selectedItem,
  loadingMore,
  onScroll,
  onSelect,
}: CraftItemListProps) => {
  if (items.length === 0) {
    return (
      <p className="py-6 text-center text-sm text-gray-600 dark:text-gray-300">
        No craftable items were found.
      </p>
    );
  }

  return (
    <div
      className="max-h-64 space-y-2 overflow-y-auto pr-1"
      onScroll={onScroll}
    >
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
  );
};

export default CraftItemList;
