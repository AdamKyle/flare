import React from 'react';
import { match } from 'ts-pattern';

import QuestItem from './quest-item';
import { ItemTypeToView } from '../enums/item-type-to-view';
import ItemDetailsContainerProps from '../types/details/item-details-container-props';

const ItemDetailsContainer = ({
  item,
  item_type_to_view,
  location_props,
  is_found_at_location,
  on_close,
}: ItemDetailsContainerProps) => {
  return match(item_type_to_view)
    .with(ItemTypeToView.QUEST, () => {
      return (
        <QuestItem
          item={item}
          location_props={location_props}
          is_found_at_location={is_found_at_location}
          on_go_back={on_close}
        />
      );
    })
    .otherwise(() => null);
};

export default ItemDetailsContainer;
