import { useOpenCharacterBackpack } from '../hooks/use-open-character-backpack';

import IconButtonDefinition from 'ui/buttons/definitions/icon-button-definition';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

export const inventoryIconButtons = (): IconButtonDefinition[] => {
  const { openBackpack } = useOpenCharacterBackpack();

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
      onClick: () => {},
      additionalCss: 'w-full',
    },
    {
      label: 'Gems',
      icon: 'ra ra-player text-sm',
      variant: ButtonVariant.PRIMARY,
      onClick: () => {},
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
