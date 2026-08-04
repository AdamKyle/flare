import React, { ReactNode, useMemo } from 'react';

import HolyOilSelectionProps from './types/holy-oil-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const buildHolyOilOptions = (
  oils: HolyOilSelectionProps['oils']
): DropdownItem[] =>
  oils.map((slot) => ({
    label: `${slot.item.name} (Amount: ${slot.amount})`,
    value: slot.id,
  }));

const HolyOilSelection = ({
  oils,
  selectedSlotId,
  onSelect,
}: HolyOilSelectionProps): ReactNode => {
  const options = useMemo(() => buildHolyOilOptions(oils), [oils]);

  const preSelectedItem = options.find(
    (option) => option.value === selectedSlotId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label
        id="work-bench-holy-oil-label"
        className="mb-2 block font-semibold"
      >
        Holy Oil
      </label>

      <Dropdown
        aria_labelled_by="work-bench-holy-oil-label"
        items={options}
        force_clear={selectedSlotId === null}
        selection_placeholder="Select a Holy Oil"
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
      />
    </div>
  );
};

export default HolyOilSelection;
