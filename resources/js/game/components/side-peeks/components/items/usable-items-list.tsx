import { isEmpty } from 'lodash';
import React from 'react';

import UsableItemsListProps from './types/usable-items-list-props';
import UsableItem from './usable-item';
import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const UsableItemsList = ({
  items,
  on_scroll_to_end,
  on_item_clicked,
}: UsableItemsListProps) => {
  const renderUsableItemSlots = () => {
    if (isEmpty(items)) {
      return (
        <div className="py-4 text-center">
          You don't have any usable items. You can craft these through Alchemy.
          Players can craft items that buff their stats and attack, deal damage
          to their opponents kingdoms or apply buffs to their items in the form
          of Holy Oils.
        </div>
      );
    }

    return items.map((item: BaseUsableItemDefinition) => (
      <UsableItem key={item.slot_id} item={item} on_click={on_item_clicked} />
    ));
  };

  return (
    <div className="h-full w-full text-gray-800 dark:text-gray-200">
      <InfiniteScroll handle_scroll={on_scroll_to_end} additional_css={'my-2'}>
        {renderUsableItemSlots()}
      </InfiniteScroll>
    </div>
  );
};

export default UsableItemsList;
