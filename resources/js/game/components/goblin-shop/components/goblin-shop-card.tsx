import clsx from 'clsx';
import React from 'react';

import GoblinShopPurchaseForm from './goblin-shop-purchase-form';
import { planeTextItemColors } from '../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import GoblinShopCardProps from '../types/goblin-shop-card-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const GoblinShopCard = ({
  item,
  view_item,
  action_disabled,
  gold_bars,
}: GoblinShopCardProps) => {
  const itemColor = planeTextItemColors(item);

  return (
    <>
      <div className="flex items-start justify-between">
        <h3
          id={`item-${item.item_id}-name`}
          className={clsx(
            'flex-1 text-lg font-semibold break-words',
            itemColor
          )}
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
        {item.description}
      </p>
      <GoblinShopPurchaseForm
        item={item}
        gold_bars={gold_bars}
        purchase_disabled={action_disabled}
      />
    </>
  );
};

export default GoblinShopCard;
