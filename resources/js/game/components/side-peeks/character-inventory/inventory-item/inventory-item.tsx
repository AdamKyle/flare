import { isNil } from 'lodash';
import React from 'react';

import { useGetInventoryItemDetails } from './api/hooks/use-get-inventory-item-details';
import InventoryItemProps from './types/inventory-item-props';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const InventoryItem = ({
  item_id,
  type_of_item,
  character_id,
}: InventoryItemProps) => {
  const { error, loading, data } = useGetInventoryItemDetails({
    character_id,
    item_id,
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY_ITEM,
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return null;
  }

  if (isNil(data)) {
    return <GameDataError />;
  }

  return <div>Hello World</div>;
};

export default InventoryItem;
