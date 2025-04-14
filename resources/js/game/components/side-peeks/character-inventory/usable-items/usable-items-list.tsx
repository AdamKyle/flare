import { isEmpty } from 'lodash';
import React from 'react';

import UsableItemsListProps from './types/usable-items-list-props';
import UsableItem from './usable-item';
import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const UsableItemsList = ({ items, on_scroll_to_end }: UsableItemsListProps) => {
  const renderUsableItemSlots = () => {
    if (isEmpty(items)) {
      return (
        <div className="text-center py-4">
          You don't have any usable items. You can craft these through Alchemy.
          Players can craft items that buff their stats and attack, deal damage
          to their opponents kingdoms or apply buffs to their items in the form
          of Holy Oils.
        </div>
      );
    }

    return items.map((item: BaseInventoryItemDefinition) => (
      <UsableItem key={item.slot_id} item={item} />
    ));
  };

  return (
    <div className="w-full h-full text-gray-800 dark:text-gray-200">
      <InfiniteScroll handle_scroll={on_scroll_to_end} additional_css={'my-2'}>
        {renderUsableItemSlots()}
      </InfiniteScroll>
    </div>
  );
};

export default UsableItemsList;
