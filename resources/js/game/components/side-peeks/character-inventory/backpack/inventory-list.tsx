import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import BackpackItem from './backpack-item';
import InventoryListProps from './types/inventory-list-props';
import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const InventoryList = ({
  items,
  is_quest_items,
  on_scroll_to_end,
}: InventoryListProps): ReactNode => {
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

    return items.map((item: BaseInventoryItemDefinition) => (
      <BackpackItem key={item.slot_id} item={item} />
    ));
  };

  return (
    <div className="w-full h-full text-gray-800 dark:text-gray-200">
      <InfiniteScroll handle_scroll={on_scroll_to_end} additional_css={'my-2'}>
        {renderBackPackItems()}
      </InfiniteScroll>
    </div>
  );
};

export default InventoryList;
