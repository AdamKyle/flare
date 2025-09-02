import React, { useState } from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MonsterExplorationConfiguration = () => {
  const [selectedTimeLength, setSelectedTimeLength] = useState<number | null>(
    null
  );
  const [selectedAttackType, setSelectedAttackType] = useState<string | null>(
    null
  );

  const timeSelection = [
    {
      label: 'One Hour',
      value: 1,
    },
    {
      label: 'Two Hours',
      value: 2,
    },
    {
      label: 'Four Hours',
      value: 4,
    },
    {
      label: 'Eight Hours',
      value: 8,
    },
  ];

  const attackTypes = [
    {
      label: 'Attack',
      value: 'attack',
    },
    {
      label: 'Cast',
      value: 'cast',
    },
    {
      label: 'Cast and Attack',
      value: 'cast-and-attack',
    },
    {
      label: 'Attack and Cast',
      value: 'attack-and-cast',
    },
    {
      label: 'Defend',
      value: 'defend',
    },
  ];

  const handleTimeSelection = (selectedValue: DropdownItem) => {
    setSelectedTimeLength(Number(selectedValue.value));
  };

  const handleAttackTypeSelected = (selectedValue: DropdownItem) => {
    setSelectedAttackType(String(selectedValue.value));
  };

  return (
    <div className="my-4 space-y-4">
      <Dropdown
        items={timeSelection}
        on_select={handleTimeSelection}
        selection_placeholder={'Select length of time'}
        additional_scroll_css={'mb-4'}
      />
      <Dropdown
        items={attackTypes}
        on_select={handleAttackTypeSelected}
        selection_placeholder={'Select the attack type'}
      />
      <Button
        on_click={() => {}}
        label={'Begin Exploration'}
        variant={ButtonVariant.SUCCESS}
        additional_css={'block mx-auto'}
      />
      <Button
        on_click={() => {}}
        label={'Close'}
        variant={ButtonVariant.DANGER}
        additional_css={'block mx-auto'}
      />
    </div>
  );
};

export default MonsterExplorationConfiguration;
