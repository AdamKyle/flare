import React, { ReactNode, useMemo } from 'react';

import GemTierSelectionProps from './types/gem-tier-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const buildGemTierOptions = (
  tiers: GemTierSelectionProps['tiers']
): DropdownItem[] =>
  tiers.map((_tier, index) => ({
    label: `Tier ${index + 1}`,
    value: index + 1,
  }));

const GemTierSelection = ({
  tiers,
  selectedTier,
  onSelect,
}: GemTierSelectionProps): ReactNode => {
  const options = useMemo(() => buildGemTierOptions(tiers), [tiers]);

  const preSelectedItem = options.find(
    (option) => option.value === selectedTier
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label id="gem-crafting-tier-label" className="mb-2 block font-semibold">
        Gem tier
      </label>

      <Dropdown
        aria_labelled_by="gem-crafting-tier-label"
        items={options}
        selection_placeholder="Select a Gem tier"
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
      />
    </div>
  );
};

export default GemTierSelection;
