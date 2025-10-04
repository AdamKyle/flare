import clsx from 'clsx';
import React from 'react';

import { formatNumberWithCommas } from '../../../util/format-number';
import { backpackItemTextColors } from '../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import GoblinShopCardProps from '../types/goblin-shop-card-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const GoblinShopCard = ({
  item,
  view_item,
  action_disabled,
}: GoblinShopCardProps) => {
  const itemColor = backpackItemTextColors(item);

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
      <p className="mt-1 font-medium text-yellow-600 dark:text-yellow-400">
        Cost: {formatNumberWithCommas(item.gold_bars_cost ?? 0)} g
      </p>
      <div className="mt-4 flex flex-wrap gap-2">
        <Button
          on_click={() => {}}
          label="Buy"
          variant={ButtonVariant.PRIMARY}
          disabled={action_disabled}
        />
      </div>
    </>
  );
};

export default GoblinShopCard;
