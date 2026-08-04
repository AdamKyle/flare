import React, { ReactNode } from 'react';

import { QueenAction } from '../enums/queen-action';
import QueenActionSelectionProps from './types/queen-action-selection-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const QueenActionSelection = ({
  onSelect,
}: QueenActionSelectionProps): ReactNode => (
  <fieldset className="space-y-3">
    <legend className="font-semibold">Choose a Queen of Hearts action</legend>
    <Button
      label="Re-Roll Item"
      on_click={() => onSelect(QueenAction.REROLL_ITEM)}
      variant={ButtonVariant.PRIMARY}
      additional_css="w-full"
    />
    <Button
      label="Move Enchants"
      on_click={() => onSelect(QueenAction.MOVE_ENCHANTS)}
      variant={ButtonVariant.PRIMARY}
      additional_css="w-full"
    />
  </fieldset>
);

export default QueenActionSelection;
