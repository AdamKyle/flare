import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import {
  backpackBaseItemStyles,
  backpackBorderStyles,
  backpackButtonBackground,
  backpackFocusRingStyles,
  backpackItemTextColors,
} from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import BackpackItemProps from '../../../character-sheet/partials/character-inventory/types/backpack-item-props';

const GenericItem = ({
  item,
  on_click,
  is_selected,
  on_item_selected,
  is_selection_disabled,
}: BackpackItemProps): ReactNode => {
  const itemColor = backpackItemTextColors(item);
  const checkboxId = 'select-' + item.slot_id;
  const titleId = 'item-title-' + item.slot_id;
  const detailsId = 'item-details-' + item.slot_id;

  const handleViewItem = () => {
    if (!on_click) {
      return;
    }

    on_click(item);
  };

  const getAttack = (equippableItem: EquippableItemWithBase): number =>
    equippableItem.raw_damage ?? equippableItem.base_damage ?? 0;

  const getAc = (equippableItem: EquippableItemWithBase): number =>
    equippableItem.raw_ac ?? equippableItem.base_ac ?? 0;

  const renderQuestDetails = (
    questItem: BaseQuestItemDefinition
  ): ReactNode => {
    if (questItem.effect === null) {
      return null;
    }

    return (
      <span>
        <strong>Effects</strong>: {questItem.effect}
      </span>
    );
  };

  const renderEquippableDetails = (
    equippableItem: EquippableItemWithBase
  ): ReactNode => {
    return (
      <>
        <span>
          <strong>Type</strong>: {equippableItem.type}
        </span>{' '}
        |{' '}
        <span>
          <strong>Damage</strong>: {getAttack(equippableItem)}
        </span>{' '}
        |{' '}
        <span>
          <strong>AC</strong>: {getAc(equippableItem)}
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

  const renderCheckbox = () => {
    if (item.type === InventoryItemTypes.QUEST) {
      return null;
    }

    return (
      <div className="mt-6 self-start">
        <input
          id={checkboxId}
          type="checkbox"
          checked={!!is_selected}
          onChange={(event) =>
            on_item_selected?.(item.slot_id, event.target.checked)
          }
          className="accent-danube-600 dark:accent-danube-500 focus-visible:ring-danube-500 dark:focus-visible:ring-danube-400 h-6 w-6 shrink-0 rounded-md border-2 border-gray-700 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-white focus-visible:outline-none dark:border-gray-300 dark:focus-visible:ring-offset-gray-900"
          aria-describedby={detailsId}
          disabled={is_selection_disabled}
        />
        <label htmlFor={checkboxId} className="sr-only">
          Select {item.name}
        </label>
      </div>
    );
  };

  const isQuest = item.type === InventoryItemTypes.QUEST;

  return (
    <div className="grid grid-cols-[auto_1fr] items-start gap-3">
      {renderCheckbox()}

      <button
        className={clsx(
          backpackBaseItemStyles(),
          backpackFocusRingStyles(item),
          backpackBorderStyles(item),
          backpackButtonBackground(item),
          'w-full',
          isQuest && 'col-span-2'
        )}
        onClick={handleViewItem}
        aria-labelledby={titleId}
        aria-describedby={detailsId}
      >
        <i className="ra ra-bone-knife text-2xl text-gray-800 dark:text-gray-600" />
        <div className="text-left">
          <div
            id={titleId}
            className={clsx('text-lg font-semibold', itemColor)}
          >
            {item.name}
          </div>
          <p className={clsx('my-2', itemColor)}>{item.description}</p>
          <div id={detailsId} className={clsx('text-sm', itemColor)}>
            {renderItemDetails()}
          </div>
        </div>
      </button>
    </div>
  );
};

export default GenericItem;
