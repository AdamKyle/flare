import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { InventoryItemTypes } from './enums/inventory-item-types';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from './styles/backpack-item-styles';
import BackpackItemProps from './types/backpack-item-props';

const BackpackItem = ({ item }: BackpackItemProps): ReactNode => {
  const itemColor = backpackItemTextColors(item);

  const renderItemDetails = (): ReactNode => {
    if (item.type === InventoryItemTypes.QUEST) {
      if (item.effect !== null) {
        return (
          <span>
            <strong>Effects</strong>: {item.effect}
          </span>
        );
      }

      return null;
    }

    return (
      <>
        <span>
          <strong>Type</strong>: {item.type}
        </span>{' '}
        |{' '}
        <span>
          <strong>Damage</strong>: {item.attack}
        </span>{' '}
        |{' '}
        <span>
          <strong>AC</strong>: {item.ac}
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

export default BackpackItem;
