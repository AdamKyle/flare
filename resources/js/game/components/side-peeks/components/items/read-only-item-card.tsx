import clsx from 'clsx';
import React, { ReactNode } from 'react';

import QuestItemOwnershipState from './enums/quest-item-ownership-state';
import ReadOnlyItemCardDensity from './enums/read-only-item-card-density';
import { readOnlyItemCardCompactBaseStyles } from './styles/read-only-item-card-styles';
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
 * Quest Item branch instead of duplicating the visual treatment. The
 * optional `ownership_state` renders a truthful `Has Item`/`Had Item`
 * status when an authoritative Character adapter supplies one; no badge
 * renders when ownership_state is absent, since absence never implies a
 * negative ownership claim.
 */
const ReadOnlyItemCard = ({
  item_id: itemId,
  name,
  description,
  effect,
  usable,
  ownership_state: ownershipState,
  density = ReadOnlyItemCardDensity.DEFAULT,
  on_click: onClick,
}: ReadOnlyItemCardProps): ReactNode => {
  const titleId = 'read-only-item-title-' + itemId;
  const detailsId = 'read-only-item-details-' + itemId;
  const isCompact = density === ReadOnlyItemCardDensity.COMPACT;

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

  const renderOwnershipState = (): ReactNode => {
    if (!ownershipState) {
      return null;
    }

    if (ownershipState === QuestItemOwnershipState.HAS) {
      return (
        <span className="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
          <i className="fas fa-check" aria-hidden="true" />
          Has Item
        </span>
      );
    }

    return (
      <span className="bg-danube-100 text-danube-800 dark:bg-danube-900 dark:text-danube-200 mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold">
        <i className="fas fa-history" aria-hidden="true" />
        Had Item
      </span>
    );
  };

  const baseStyles = isCompact
    ? readOnlyItemCardCompactBaseStyles()
    : backpackBaseItemStyles();

  const nameClassName = clsx(
    isCompact ? 'text-sm font-semibold' : 'text-lg font-semibold',
    itemColor
  );

  const descriptionClassName = clsx(
    isCompact ? 'mt-1 text-xs' : 'my-2',
    itemColor
  );

  const detailsClassName = clsx(
    isCompact ? 'mt-1 text-xs' : 'text-sm',
    itemColor
  );

  return (
    <button
      type="button"
      className={clsx(
        baseStyles,
        backpackFocusRingStyles(styleShape),
        backpackBorderStyles(styleShape),
        backpackButtonBackground(styleShape)
      )}
      onClick={() => onClick(itemId)}
      aria-labelledby={titleId}
      aria-describedby={detailsId}
    >
      <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600" />
      <div className={clsx('text-left', isCompact && 'min-w-0 flex-1')}>
        <div id={titleId} className={nameClassName}>
          {name}
        </div>
        <p className={descriptionClassName}>{description}</p>
        <div id={detailsId} className={detailsClassName}>
          {renderEffect()}
        </div>
        {renderOwnershipState()}
      </div>
    </button>
  );
};

export default ReadOnlyItemCard;
