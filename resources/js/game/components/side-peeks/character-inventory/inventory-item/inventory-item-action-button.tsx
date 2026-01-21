import React from 'react';

import InventoryItemActionButtonProps from './types/inventory-item-action-button-props';
import { ItemActions } from '../../../../reusable-components/item/enums/item-actions';

import DropDownButton from 'ui/buttons/drop-down-button';

const InventoryItemActionButton = ({
  on_select_action,
}: InventoryItemActionButtonProps) => {
  const data = {
    dropdown_label: 'Actions',
    items: [
      {
        label: 'Move to set',
        value: ItemActions.MOVE_TO_SET,
        aria_label: 'Move to set',
      },
      {
        label: 'Sell',
        value: ItemActions.SELL,
        aria_label: 'Sell',
      },
      {
        label: 'List',
        value: ItemActions.LIST,
        aria_label: 'List',
      },
      {
        label: 'Destroy',
        value: ItemActions.DESTROY,
        aria_label: 'Destroy',
      },
      {
        label: 'Disenchant',
        value: ItemActions.DISENCHANT,
        aria_label: 'Disenchant',
      },
    ],
  };

  return <DropDownButton data={data} on_select={on_select_action} />;
};

export default InventoryItemActionButton;
