import React from 'react';

import InventoryItemActionButtonProps from './types/inventory-item-action-button-props';
import { ItemActions } from '../../../../reusable-components/item/enums/item-actions';

import DropdownButton from 'ui/buttons/drop-down-button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const InventoryItemActionButton = ({
  on_select_action,
}: InventoryItemActionButtonProps) => {
  const itemClassName =
    'inline-flex w-full items-center justify-start rounded-md px-3 py-2 text-left transition-colors ' +
    'bg-gray-300 dark:bg-gray-500 hover:bg-gray-400 hover:text-gray-600 ' +
    'dark:bg-gray-400 dark:hover:bg-gray-400 hover:text-gray-800 text-gray-600 dark:text-gray-800 ' +
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 dark:focus-visible:ring-gray-600 my-1';

  return (
    <DropdownButton label="Actions" variant={ButtonVariant.PRIMARY}>
      <button
        type="button"
        onClick={() => on_select_action(ItemActions.MOVE_TO_SET)}
        className={itemClassName}
        role="menuitem"
      >
        Move to set
      </button>
      <button
        type="button"
        onClick={() => on_select_action(ItemActions.SELL)}
        className={itemClassName}
        role="menuitem"
      >
        Sell
      </button>
      <button
        type="button"
        onClick={() => on_select_action(ItemActions.LIST)}
        className={itemClassName}
        role="menuitem"
      >
        List
      </button>
      <button
        type="button"
        onClick={() => on_select_action(ItemActions.DESTROY)}
        className={itemClassName}
        role="menuitem"
      >
        Destroy
      </button>
      <button
        type="button"
        onClick={() => on_select_action(ItemActions.DISENCHANT)}
        className={itemClassName}
        role="menuitem"
      >
        Disenchant
      </button>
    </DropdownButton>
  );
};

export default InventoryItemActionButton;
