import clsx from 'clsx';
import React from 'react';

import UsableItemProps from './types/usable-item-props';
import UsableItemCardContent from './usable-item-card-content';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
} from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

const UsableItem = ({ item, on_click }: UsableItemProps) => {
  const handleClickItem = () => {
    on_click(item.item_id);
  };

  return (
    <button
      className={clsx(
        backpackBaseItemStyles(),
        backpackFocusRingStyles(item),
        backpackBorderStyles(item),
        backpackButtonBackground(item)
      )}
      onClick={handleClickItem}
    >
      <UsableItemCardContent item={item} />
    </button>
  );
};

export default UsableItem;
