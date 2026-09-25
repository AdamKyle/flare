import SetFooterOptionsParams from '../definitions/set-footer-options-params';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

export const buildSetFooterOptions = (
  params: SetFooterOptionsParams
): SidePeekOptionDefinition[] => {
  const { availability } = params;

  if (availability.can_unequip) {
    return [
      {
        id: 'unequip-set',
        label: 'Unequip Set',
        loading_label: 'Unequipping...',
        variant: ButtonVariant.DANGER,
        disabled: params.is_equipment_restricted,
        loading: params.is_unequipping,
        on_click: params.on_unequip,
      },
    ];
  }

  const options: SidePeekOptionDefinition[] = [];

  if (availability.can_equip) {
    options.push({
      id: 'equip-set',
      label: 'Equip Set',
      loading_label: 'Equipping...',
      variant: ButtonVariant.SUCCESS,
      disabled: params.is_equipment_restricted,
      loading: params.is_equipping,
      on_click: params.on_equip,
    });
  }

  if (availability.can_empty) {
    options.push({
      id: 'empty-set',
      label: 'Empty Set',
      loading_label: 'Emptying...',
      variant: ButtonVariant.DANGER,
      loading: params.is_emptying,
      on_click: params.on_empty,
    });
  }

  return options;
};
