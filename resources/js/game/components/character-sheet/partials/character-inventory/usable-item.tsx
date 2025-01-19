import clsx from 'clsx';
import React, { ReactNode } from 'react';

import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from './styles/backpack-item-styles';
import UsableItemProps from './types/usable-item-props';
import { formatNumberWithCommas } from '../../../../util/format-number';

const UsableItem = ({ item }: UsableItemProps): ReactNode => {
  const itemColor = backpackItemTextColors(item);

  const renderItemDetails = (): ReactNode => {
    if (item.damages_kingdoms) {
      if (item.effect !== null) {
        return (
          <span>
            <strong>Damage dealt when dropped</strong>:{' '}
            {formatNumberWithCommas(item.kingdom_damage || 0)}
          </span>
        );
      }

      return null;
    }

    return (
      <>
        <span>
          <strong>Lasts For (Minutes)</strong>: {item.lasts_for}
        </span>
      </>
    );
  };

  return (
    <button
      className={clsx(
        backpackBaseItemStyles(),
        backpackFocusRingStyles(item),
        backpackBorderStyles(item),
        backpackButtonBackground(item)
      )}
    >
      <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600"></i>
      <div className="text-left">
        <div className={clsx('text-lg font-semibold', itemColor)}>
          {item.name}
        </div>
        <p className={clsx('my-2', itemColor)}>{item.description}</p>
        <div className={clsx('text-sm', itemColor)}>{renderItemDetails()}</div>
      </div>
    </button>
  );
};

export default UsableItem;
