import React, { ReactNode } from 'react';

import { SeerAction } from '../enums/seer-action';
import SeerActionSelectionProps from './types/seer-action-selection-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const SeerActionSelection = ({
  onSelect,
}: SeerActionSelectionProps): ReactNode => {
  const handleSelectManageSockets = (): void => {
    onSelect(SeerAction.MANAGE_SOCKETS);
  };

  const handleSelectAttachGem = (): void => {
    onSelect(SeerAction.ATTACH_GEM);
  };

  const handleSelectRemoveGem = (): void => {
    onSelect(SeerAction.REMOVE_GEM);
  };

  return (
    <fieldset className="space-y-3">
      <legend className="font-semibold">Choose a Seer Camp action</legend>

      <Button
        label="Create/ReRoll Sockets"
        on_click={handleSelectManageSockets}
        variant={ButtonVariant.PRIMARY}
      />
      <Button
        label="Attach Gems"
        on_click={handleSelectAttachGem}
        variant={ButtonVariant.PRIMARY}
      />
      <Button
        label="Remove Gem"
        on_click={handleSelectRemoveGem}
        variant={ButtonVariant.PRIMARY}
      />
    </fieldset>
  );
};

export default SeerActionSelection;
