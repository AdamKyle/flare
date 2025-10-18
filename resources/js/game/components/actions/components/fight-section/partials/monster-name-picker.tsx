import React from 'react';

import MonsterNamePickerProps from '../types/partials/monster-name-picker-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MonsterNamePicker = ({
  display_name,
  monsters,
  current_index,
  on_select,
}: MonsterNamePickerProps) => {
  if (!monsters || monsters.length === 0) {
    return null;
  }

  const dropdownItems: DropdownItem[] = monsters.map((monster, index) => ({
    value: index,
    label: monster.name,
  }));

  const preSelectedItem: DropdownItem | undefined = monsters[current_index]
    ? { value: current_index, label: monsters[current_index].name }
    : undefined;

  const handleSelect = (item: DropdownItem) => {
    const selectedIndex =
      typeof item.value === 'number' ? item.value : Number(item.value);

    if (Number.isNaN(selectedIndex)) {
      return;
    }

    on_select(selectedIndex);
  };

  return (
    <Dropdown
      items={dropdownItems}
      on_select={handleSelect}
      pre_selected_item={preSelectedItem}
      selection_placeholder={display_name}
      all_click_outside
      focus_selected_on_open
    />
  );
};

export default MonsterNamePicker;
