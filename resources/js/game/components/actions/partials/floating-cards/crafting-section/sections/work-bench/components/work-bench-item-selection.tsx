import React, { ReactNode, useMemo } from 'react';

import WorkBenchItemSelectionProps from './types/work-bench-item-selection-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const buildWorkBenchItemOptions = (
  items: WorkBenchItemSelectionProps['items']
): DropdownItem[] =>
  items.map((slot) => ({
    label: slot.item.affix_name,
    value: slot.id,
  }));

const WorkBenchItemSelection = ({
  items,
  selectedSlotId,
  onSelect,
}: WorkBenchItemSelectionProps): ReactNode => {
  const options = useMemo(() => buildWorkBenchItemOptions(items), [items]);

  const preSelectedItem = options.find(
    (option) => option.value === selectedSlotId
  );

  const handleSelect = (option: DropdownItem): void => {
    onSelect(Number(option.value));
  };

  return (
    <div>
      <label
        id="work-bench-target-item-label"
        className="mb-2 block font-semibold"
      >
        Target item
      </label>

      <Dropdown
        aria_labelled_by="work-bench-target-item-label"
        items={options}
        selection_placeholder="Select an item"
        pre_selected_item={preSelectedItem}
        on_select={handleSelect}
      />
    </div>
  );
};

export default WorkBenchItemSelection;
