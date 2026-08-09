import React, { ReactNode } from 'react';

import EquippedItem from './equipped-item';
import EquippedItemProps from './types/equipped-items-props';
import BaseEquippedItemDetails from '../../../../../api-definitions/items/base-equipped-item-details';

const EquippedItems = ({
  items_equipped,
  stat_type,
}: EquippedItemProps): ReactNode => {
  return items_equipped.map((itemEquipped: BaseEquippedItemDetails) => {
    const key =
      itemEquipped.item_details.slot_id ?? itemEquipped.item_details.item_id;

    return (
      <EquippedItem
        key={key}
        equipped_item={itemEquipped}
        stat_type={stat_type}
      />
    );
  });
};

export default EquippedItems;
