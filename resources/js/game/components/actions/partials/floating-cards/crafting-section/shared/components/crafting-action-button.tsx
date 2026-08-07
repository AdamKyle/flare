import React, { ReactNode } from 'react';

import CraftingActionButtonProps from './types/crafting-action-button-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftingActionButton = ({
  label,
  on_click,
  disabled,
  variant = ButtonVariant.SUCCESS,
  aria_label,
}: CraftingActionButtonProps): ReactNode => {
  return (
    <Button
      label={label}
      on_click={on_click}
      variant={variant}
      disabled={disabled}
      aria_label={aria_label}
      additional_css="w-full"
    />
  );
};

export default CraftingActionButton;
