import React from 'react';

import { formatNumberWithCommas } from '../../../util/format-number';
import ShopCardProps from '../types/shop-card-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const ShopCard = ({ item, view_item }: ShopCardProps) => {
  return (
    <>
      <div className="flex items-start justify-between">
        <h3
          id={`item-${item.id}-name`}
          className="flex-1 text-lg font-semibold text-danube-600 dark:text-danube-300 break-words"
        >
          {item.name}
        </h3>
        <LinkButton
          label="view"
          variant={ButtonVariant.PRIMARY}
          on_click={() => view_item(item.id)}
        />
      </div>
      <p className="mt-2 text-gray-700 dark:text-gray-300">
        Restores 50 HP over 10 seconds.
      </p>
      <p className="mt-1 font-medium text-yellow-600 dark:text-yellow-400">
        Cost: {formatNumberWithCommas(item.cost)} g
      </p>
      <div className="mt-4 flex flex-wrap gap-2">
        <Button
          on_click={() => {}}
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
