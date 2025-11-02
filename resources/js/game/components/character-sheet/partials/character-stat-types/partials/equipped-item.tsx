import clsx from 'clsx';
import React, { ReactNode } from 'react';

import AttachedAffixes from './attached-affixes';
import EquippedItemProps from './types/equipped-item-props';
import { itemTextColor } from '../../../../../util/item-colors/item-text-color';

const EquippedItem = ({
  equipped_item,
  stat_type,
}: EquippedItemProps): ReactNode => {
  const itemColor = itemTextColor(equipped_item.item_details);

  return (
    <li>
      <span className={clsx(itemColor)}>{equipped_item.item_details.name}</span>{' '}
      <span className="text-green-700 dark:text-green-500">
        (+{(equipped_item.item_base_stat * 100).toFixed(2)}%)
      </span>
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
