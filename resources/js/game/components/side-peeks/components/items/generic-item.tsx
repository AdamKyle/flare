import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
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
    if (!on_click) return;
    on_click(item);
  };

  const getAttack = (i: EquippableItemWithBase): number =>
    i.raw_damage ?? i.base_damage ?? 0;

  const getAc = (i: EquippableItemWithBase): number =>
    i.raw_ac ?? i.base_ac ?? 0;

  const renderQuestDetails = (i: BaseQuestItemDefinition): ReactNode => {
    if (i.effect === null) return null;

    return (
      <span>
        <strong>Effects</strong>: {i.effect}
      </span>
    );
  };

  const renderEquippableDetails = (i: EquippableItemWithBase): ReactNode => {
    return (
      <>
        <span>
          <strong>Type</strong>: {i.type}
        </span>{' '}
        |{' '}
        <span>
          <strong>Damage</strong>: {getAttack(i)}
        </span>{' '}
        |{' '}
        <span>
          <strong>AC</strong>: {getAc(i)}
        </span>
      </>
    );
  };

  const renderItemDetails = (): ReactNode => {
    if ('effect' in item) {
      return renderQuestDetails(item as BaseQuestItemDefinition);
    }

    return renderEquippableDetails(item as EquippableItemWithBase);
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
