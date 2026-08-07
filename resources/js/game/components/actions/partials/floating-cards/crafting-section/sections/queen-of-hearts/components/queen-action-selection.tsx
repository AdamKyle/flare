import React, { ReactNode } from 'react';

import CraftingActionButton from '../../../shared/components/crafting-action-button';
import { QueenAction } from '../enums/queen-action';
import QueenActionSelectionProps from './types/queen-action-selection-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const QueenActionSelection = ({
  onSelect,
}: QueenActionSelectionProps): ReactNode => (
  <fieldset className="space-y-2">
    <legend className="font-semibold">Choose a Queen of Hearts action</legend>
    <CraftingActionButton
      label="Re-Roll Item"
      on_click={() => onSelect(QueenAction.REROLL_ITEM)}
      variant={ButtonVariant.PRIMARY}
    />
    <CraftingActionButton
      label="Move Enchants"
      on_click={() => onSelect(QueenAction.MOVE_ENCHANTS)}
      variant={ButtonVariant.PRIMARY}
    />
  </fieldset>
);

export default QueenActionSelection;
