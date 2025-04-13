import { useOpenCharacterBackpack } from '../hooks/use-open-character-backpack';
import { useOpenCharacterGemBag } from '../hooks/use-open-character-gem-bag';
import { useOpenCharacterUsableInventory } from '../hooks/use-open-character-usable-inventory';
import InventoryIconButtonsParamDefinition from './definitions/inventory-icon-buttons-param-definition';

import IconButtonDefinition from 'ui/buttons/definitions/icon-button-definition';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export const inventoryIconButtons = (
  params: InventoryIconButtonsParamDefinition
): IconButtonDefinition[] => {
  const { openBackpack } = useOpenCharacterBackpack(params);
  const { openUsableInventory } = useOpenCharacterUsableInventory();
  const { openGemBag } = useOpenCharacterGemBag(params);

  return [
    {
      label: 'Backpack',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: openBackpack,
      additionalCss: 'w-full',
    },
    {
      label: 'Usable',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: openUsableInventory,
      additionalCss: 'w-full',
    },
    {
      label: 'Gems',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: openGemBag,
      additionalCss: 'w-full',
    },
    {
      label: 'Sets',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: () => {},
      additionalCss: 'w-full',
    },
  ];
};
