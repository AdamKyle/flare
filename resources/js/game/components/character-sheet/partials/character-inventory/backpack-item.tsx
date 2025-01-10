import clsx from 'clsx';
import React, { ReactNode } from 'react';

import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from './styles/backpack-item-styles';
import BackpackItemState from './types/backpack-item-state';

const BackpackItem = ({ item }: BackpackItemState): ReactNode => {
  const itemColor = backpackItemTextColors(item);

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
        <div className={clsx('text-sm', itemColor)}>
          <span>Type: {item.type}</span> | <span>Damage: {item.attack}</span> |{' '}
          <span>AC: {item.ac}</span>
        </div>
      </div>
    </button>
  );
};

export default BackpackItem;
