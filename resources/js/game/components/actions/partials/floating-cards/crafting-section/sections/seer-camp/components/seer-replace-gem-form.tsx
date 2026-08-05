import React, { ReactNode, useMemo } from 'react';

import SeerReplaceGemFormProps from './types/seer-replace-gem-form-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SeerReplaceGemForm = ({
  attachedGems,
  selectedGemId,
  onSelect,
}: SeerReplaceGemFormProps): ReactNode => {
  const options = useMemo<DropdownItem[]>(
    () => attachedGems.map((gem) => ({ label: gem.name, value: gem.id })),
    [attachedGems]
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  const preSelectedItem = options.find(
    (option) => option.value === selectedGemId
  );

  return (
    <div className="space-y-3">
      <label id="seer-replace-gem-label" className="block font-semibold">
        Attached Gem to replace
      </label>

      <Dropdown
        aria_labelled_by="seer-replace-gem-label"
        items={options}
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
        selection_placeholder="Select an attached Gem"
      />
    </div>
  );
};

export default SeerReplaceGemForm;
