import React from 'react';

import { ItemBaseTypes } from '../../../reusable-components/item/enums/item-base-type';
import { getType } from '../../../reusable-components/item/utils/get-type';
import { formatNumberWithCommas } from '../../../util/format-number';
import { armourPositions } from '../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import ShopCardProps from '../types/shop-card-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const ShopCard = ({ item, view_item, compare_item }: ShopCardProps) => {
  const itemType = getType(item, armourPositions);

  const renderAttackOrDefence = () => {
    if (itemType === ItemBaseTypes.Armour) {
      return (
        <span>
          <strong>AC:</strong> {`+${item.raw_ac}`}
        </span>
      );
    }

    return (
      <span>
        <strong>Damage:</strong> {`+${item.raw_damage}`}
      </span>
    );
  };

  return (
    <>
      <div className="flex items-start justify-between">
        <h3
          id={`item-${item.item_id}-name`}
          className="flex-1 text-lg font-semibold text-danube-600 dark:text-danube-300 break-words"
        >
          {item.name}
        </h3>
        <LinkButton
          label="view"
          variant={ButtonVariant.PRIMARY}
          on_click={() => view_item(item.item_id)}
        />
      </div>
      <p className="mt-2 text-gray-700 dark:text-gray-300">
        {renderAttackOrDefence()}
      </p>
      <p className="mt-1 font-medium text-yellow-600 dark:text-yellow-400">
        Cost: {formatNumberWithCommas(item.cost)} g
      </p>
      <div className="mt-4 flex flex-wrap gap-2">
        <Button
          on_click={() => compare_item(item.item_id)}
          label="Compare"
          variant={ButtonVariant.SUCCESS}
        />
        <Button
          on_click={() => {}}
          label="Buy"
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label="Buy Multiple"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    </>
  );
};

export default ShopCard;
