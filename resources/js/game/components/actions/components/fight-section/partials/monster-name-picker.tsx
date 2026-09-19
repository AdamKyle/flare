import React, { useEffect, useState } from 'react';

import MonsterNameListDefinition from '../deffinitions/monster-name-list-definition';
import MonsterNamePickerProps from '../types/partials/monster-name-picker-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MonsterNamePicker = ({
  display_name,
  monsters,
  current_index,
  on_select,
}: MonsterNamePickerProps) => {
  const [openSnapshot, setOpenSnapshot] = useState<
    MonsterNameListDefinition[] | null
  >(null);

  const selectedMonster = monsters[current_index];
  const selectedMonsterId = selectedMonster ? selectedMonster.id : null;

  useEffect(() => {
    setOpenSnapshot(null);
  }, [selectedMonsterId]);

  if (!Array.isArray(monsters) || monsters.length === 0) {
    return null;
  }

  const optionSource = openSnapshot ?? monsters;

  const dropdownItems: DropdownItem[] = optionSource.map((monster) => ({
    value: monster.id,
    label: monster.name,
  }));

  const preSelectedItem: DropdownItem | undefined = selectedMonster
    ? { value: selectedMonster.id, label: selectedMonster.name }
    : undefined;

  const handleOpen = () => {
    setOpenSnapshot(monsters);
  };

  const handleClose = () => {
    setOpenSnapshot(null);
  };

  const handleSelect = (item: DropdownItem) => {
    const selectedId =
      typeof item.value === 'number' ? item.value : Number(item.value);

    if (Number.isNaN(selectedId)) {
      return;
    }

    const liveIndex = monsters.findIndex(
      (monster) => monster.id === selectedId
    );

    if (liveIndex === -1) {
      return;
    }

    on_select(liveIndex);
  };

  return (
    <Dropdown
      key={selectedMonsterId ?? 'none'}
      items={dropdownItems}
      on_select={handleSelect}
      on_clear={() => on_select(0)}
      pre_selected_item={preSelectedItem}
      selection_placeholder={display_name}
      focus_selected_on_open
      on_open={handleOpen}
      on_close={handleClose}
    />
  );
};

export default MonsterNamePicker;
