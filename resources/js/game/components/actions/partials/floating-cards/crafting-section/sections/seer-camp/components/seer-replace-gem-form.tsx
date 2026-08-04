import React, { ReactNode, useMemo } from 'react';

import SeerReplaceGemFormProps from './types/seer-replace-gem-form-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerReplaceGemForm = ({
  attachedGems,
  selectedGemId,
  replaceCost,
  submitting,
  onSelect,
  onSubmit,
}: SeerReplaceGemFormProps): ReactNode => {
  const options = useMemo<DropdownItem[]>(
    () => attachedGems.map((gem) => ({ label: gem.name, value: gem.id })),
    [attachedGems]
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div className="space-y-3">
      <label id="seer-replace-gem-label" className="block font-semibold">
        Attached Gem to replace
      </label>

      <Dropdown
        aria_labelled_by="seer-replace-gem-label"
        items={options}
        on_select={handleSelect}
        selection_placeholder="Select an attached Gem"
      />

      <p>Replace Gem cost: {replaceCost} Gold Bars.</p>

      <Button
        label="Replace Gem"
        on_click={onSubmit}
        variant={ButtonVariant.PRIMARY}
        disabled={selectedGemId === null || submitting}
      />
    </div>
  );
};

export default SeerReplaceGemForm;
