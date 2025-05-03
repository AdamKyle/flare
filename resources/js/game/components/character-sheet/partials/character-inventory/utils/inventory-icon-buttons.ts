import InventoryIconButtonsParamDefinition from './definitions/inventory-icon-buttons-param-definition';

import IconButtonDefinition from 'ui/buttons/definitions/icon-button-definition';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export const inventoryIconButtons = (
  params: InventoryIconButtonsParamDefinition
): IconButtonDefinition[] => {
  return [
    {
      label: 'Backpack',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openBackpack,
      additionalCss: 'w-full',
    },
    {
      label: 'Usable',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openUsableInventory,
      additionalCss: 'w-full',
    },
    {
      label: 'Gems',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openGemBag,
      additionalCss: 'w-full',
    },
    {
      label: 'Sets',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openSets,
      additionalCss: 'w-full',
    },
  ];
};
