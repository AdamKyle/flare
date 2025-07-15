import clsx from 'clsx';
import { isNil } from 'lodash';
import React from 'react';

import { useGetInventoryItemDetails } from './api/hooks/use-get-inventory-item-details';
import InventoryItemProps from './types/inventory-item-props';
import { ItemBaseTypes } from '../../../../reusable-components/item/enums/item-base-type';
import ItemDetails from '../../../../reusable-components/item/partials/item-details';
import { getType } from '../../../../reusable-components/item/utils/get-type';
import { armourPositions } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { backpackItemTextColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const InventoryItem = ({
  item_id,
  type_of_item,
  character_id,
  close_item_view,
}: InventoryItemProps) => {
  const { error, loading, data } = useGetInventoryItemDetails({
    character_id,
    item_id,
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY_ITEM,
  });

  if (loading) {
    return (
      <div className="px-4">
        {' '}
        <InfiniteLoader />
      </div>
    );
  }

  if (error) {
    return null;
  }

  if (isNil(data)) {
    return (
      <div className="px-4">
        {' '}
        <GameDataError />
      </div>
    );
  }

  const item = data;

  const renderItemViewScreen = () => {
    const type = getType(item, armourPositions);

    const types = [
      ItemBaseTypes.Armour,
      ItemBaseTypes.Weapon,
      ItemBaseTypes.Spell,
      ItemBaseTypes.Ring,
    ];

    if (!types.includes(type)) {
      return;
    }

    return (
      <ItemDetails
        item={item}
        show_in_between_separator
        damage_ac_on_top
        show_advanced_button
      />
    );
  };

  return (
    <>
      <div className={'text-center p-4'}>
        <Button
          on_click={close_item_view}
          label={'Close'}
          variant={ButtonVariant.SUCCESS}
        />
      </div>
      <div className={'px-4 flex flex-col gap-2 '}>
        <div>
          <h2 className={clsx(backpackItemTextColors(item), 'text-lg my-2')}>
            {item.name}
          </h2>
          <Separator />
          <p className={'my-4 text-gray-800 dark:text-gray-300'}>
            {item.description}
          </p>
          <Separator />
        </div>
        {renderItemViewScreen()}
      </div>
    </>
  );
};

export default InventoryItem;
