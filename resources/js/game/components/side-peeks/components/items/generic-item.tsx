import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import BackpackItemProps from '../../../character-sheet/partials/character-inventory/types/backpack-item-props';

const GenericItem = ({ item, on_click }: BackpackItemProps): ReactNode => {
  const itemColor = backpackItemTextColors(item);

  const handleViewItem = () => {
    if (!on_click) {
      return;
    }
    on_click(item);
  };

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

    if ('attack' in item && 'ac' in item) {
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
    }

    return null;
  };

  return (
    <button
      className={clsx(
        backpackBaseItemStyles(),
        backpackFocusRingStyles(item),
        backpackBorderStyles(item),
        backpackButtonBackground(item)
      )}
      onClick={handleViewItem}
    >
      <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600" />
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

export default GenericItem;
