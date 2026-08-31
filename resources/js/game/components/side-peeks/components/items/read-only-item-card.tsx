import clsx from 'clsx';
import React, { ReactNode } from 'react';

import ReadOnlyItemCardProps from './types/read-only-item-card-props';
import { BaseItemDetails } from '../../../../api-definitions/items/base-item-details';
import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

/**
 * Canonical read-only, non-selectable Quest Item card. Renders the exact
 * established inventory Item appearance (icon, name, description, effect)
 * for factual/relationship contexts such as Location drops, NPC rewards,
 * and Quest/Monster requirements where no inventory slot, checkbox, or
 * selection state exists. `GenericItem` reuses this presentation for its
 * Quest Item branch instead of duplicating the visual treatment.
 */
const ReadOnlyItemCard = ({
  item_id: itemId,
  name,
  description,
  effect,
  usable,
  on_click: onClick,
}: ReadOnlyItemCardProps): ReactNode => {
  const titleId = 'read-only-item-title-' + itemId;
  const detailsId = 'read-only-item-details-' + itemId;

  const styleShape: BaseItemDetails = {
    affix_count: 0,
    max_holy_stacks: 0,
    holy_stacks_applied: 0,
    holy_stacks_total_stat_increase: 0,
    is_cosmic: false,
    is_mythic: false,
    is_unique: false,
    usable,
    holy_level: null,
    damages_kingdoms: false,
    name,
    description,
    type: InventoryItemTypes.QUEST,
    cost: 0,
    item_id: itemId,
  };

  const itemColor = backpackItemTextColors(styleShape);

  const renderEffect = (): ReactNode => {
    if (!effect) {
      return null;
    }

    return (
      <span>
        <strong>Effects</strong>: {effect}
      </span>
    );
  };

  return (
    <button
      type="button"
      className={clsx(
        backpackBaseItemStyles(),
        backpackFocusRingStyles(styleShape),
        backpackBorderStyles(styleShape),
        backpackButtonBackground(styleShape),
        'w-full'
      )}
      onClick={() => onClick(itemId)}
      aria-labelledby={titleId}
      aria-describedby={detailsId}
    >
      <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600" />
      <div className="text-left">
        <div id={titleId} className={clsx('text-lg font-semibold', itemColor)}>
          {name}
        </div>
        <p className={clsx('my-2', itemColor)}>{description}</p>
        <div id={detailsId} className={clsx('text-sm', itemColor)}>
          {renderEffect()}
        </div>
      </div>
    </button>
  );
};

export default ReadOnlyItemCard;
