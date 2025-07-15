import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import GenericItem from './generic-item';
import GenericItemListProps from './types/generic-item-list-props';
import BaseInventoryItemDefinition from '../../character-inventory/api-definitions/base-inventory-item-definition';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const GenericItemList = ({
  items,
  is_quest_items,
  on_scroll_to_end,
  items_view_type,
  on_click,
}: GenericItemListProps): ReactNode => {
  const handleOnClick = (item: BaseInventoryItemDefinition) => {
    if (!on_click) {
      return;
    }

    return on_click(items_view_type, item.item_id);
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

    return items.map((item: BaseInventoryItemDefinition) => (
      <GenericItem key={item.slot_id} item={item} on_click={handleOnClick} />
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

export default GenericItemList;
