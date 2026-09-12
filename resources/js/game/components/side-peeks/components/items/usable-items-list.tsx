import { isEmpty } from 'lodash';
import React from 'react';

import UsableItemsListProps from './types/usable-items-list-props';
import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import UsableAlchemyActionCard from '../../character-inventory/usable-items/components/usable-alchemy-action-card';
import { resolveAlchemyLegalUseCount } from '../../character-inventory/usable-items/utils/resolve-alchemy-legal-use-count';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const UsableItemsList = ({
  items,
  on_scroll_to_end,
  on_item_clicked,
  active_boons: activeBoons,
  using_slot_id: usingSlotId,
  character_id: characterId,
  on_use_one: onUseOne,
  on_use_quantity: onUseQuantity,
  on_use_all: onUseAll,
  on_gem_scroll_activated: onGemScrollActivated,
}: UsableItemsListProps) => {
  const now = new Date();

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
      <UsableAlchemyActionCard
        key={item.slot_id}
        item={item}
        legal_use_count={resolveAlchemyLegalUseCount(item, activeBoons, now)}
        using_slot_id={usingSlotId}
        character_id={characterId}
        on_click={on_item_clicked}
        on_use_one={onUseOne}
        on_use_quantity={onUseQuantity}
        on_use_all={onUseAll}
        on_gem_scroll_activated={onGemScrollActivated}
      />
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
