import clsx from 'clsx';
import React, { ReactNode } from 'react';

import AttachedAffixes from './attached-affixes';
import EquippedItemProps from './types/equipped-item-props';
import { itemTextColor } from '../../../../../util/item-colors/item-text-color';
import { getStatName } from '../../../enums/stat-types';

const EquippedItem = ({
  equipped_item,
  stat_type,
}: EquippedItemProps): ReactNode => {
  const itemColor = itemTextColor(equipped_item.item_details);

  const renderItemBaseStat = () => {
    if (equipped_item.item_base_stat <= 0) {
      return (
        <span className={clsx(itemColor)}>
          {equipped_item.item_details.name}
        </span>
      );
    }

    return (
      <>
        <span className={clsx(itemColor)}>
          {equipped_item.item_details.name}
        </span>{' '}
        <span className="text-green-700 dark:text-green-500">
          (+{(equipped_item.item_base_stat * 100).toFixed(2)}%)
        </span>
      </>
    );
  };

  const renderItemStatIncrease = () => {
    if (
      !equipped_item.total_stat_increase ||
      equipped_item.total_stat_increase === 0
    ) {
      return null;
    }

    return (
      <ul className="mt-2 list-inside list-disc space-y-1 ps-5">
        <li>
          +{(equipped_item.total_stat_increase * 100).toFixed(2)}% to{' '}
          {getStatName(stat_type)}
        </li>
      </ul>
    );
  };

  if (
    equipped_item.item_base_stat <= 0 &&
    equipped_item.total_stat_increase <= 0 &&
    equipped_item.attached_affixes.length <= 0
  ) {
    return null;
  }

  return (
    <li>
      {renderItemBaseStat()}
      {renderItemStatIncrease()}
      <ul className="mt-2 list-inside list-disc space-y-1 ps-5">
        <AttachedAffixes
          attached_affixes={equipped_item.attached_affixes}
          stat_type={stat_type}
        />
      </ul>
    </li>
  );
};

export default EquippedItem;
