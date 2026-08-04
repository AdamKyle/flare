import React, { ReactNode } from 'react';

import { EnchantingItemSource } from '../enums/enchanting-item-source';
import EnchantingSourceSelectionProps from './types/enchanting-source-selection-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const EnchantingSourceSelection = ({
  onSelect,
}: EnchantingSourceSelectionProps): ReactNode => {
  const handleSelectRegular = (): void => {
    onSelect(EnchantingItemSource.REGULAR);
  };

  const handleSelectEvent = (): void => {
    onSelect(EnchantingItemSource.EVENT);
  };

  return (
    <fieldset className="space-y-3">
      <legend className="font-semibold">Choose items to enchant</legend>

      <Button
        label="Regular inventory"
        on_click={handleSelectRegular}
        variant={ButtonVariant.PRIMARY}
      />
      <Button
        label="Event items"
        on_click={handleSelectEvent}
        variant={ButtonVariant.PRIMARY}
      />
    </fieldset>
  );
};

export default EnchantingSourceSelection;
