import InventoryIconButtonsParamDefinition from './definitions/inventory-icon-buttons-param-definition';
import { GuideTargets } from '../../../../guide-quests/walkthrough/registry/guide-targets';

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
      dataGuideTarget: GuideTargets.INVENTORY_BACKPACK,
    },
    {
      label: 'Usable',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openUsableInventory,
      additionalCss: 'w-full',
      dataGuideTarget: GuideTargets.INVENTORY_USABLE,
    },
    {
      label: 'Gems',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openGemBag,
      additionalCss: 'w-full',
      dataGuideTarget: GuideTargets.INVENTORY_GEMS,
    },
    {
      label: 'Sets',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: params.openSets,
      additionalCss: 'w-full',
      dataGuideTarget: GuideTargets.INVENTORY_SETS,
    },
  ];
};
