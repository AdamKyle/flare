import React from 'react';

import PortLocationsDropDownProps from './types/port-locations-drop-down-props';
import SetSailPortDefinition from '../api/definitions/set-sail-port-definition';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const PortLocationsDropDown = ({
  ports,
  on_select,
  on_clear,
  aria_labelled_by,
}: PortLocationsDropDownProps) => {
  const portChoices: DropdownItem[] = ports.map((port) => {
    return {
      label: `${port.name} (${port.x} / ${port.y})`,
      value: port.id,
    };
  });

  const handleSelection = (selectedItem: DropdownItem) => {
    const selectedPort = ports.find(
      (port: SetSailPortDefinition) => port.id === selectedItem.value
    );

    if (!selectedPort) {
      return;
    }

    on_select(selectedPort);
  };

  return (
    <Dropdown
      items={portChoices}
      on_select={handleSelection}
      on_clear={on_clear}
      selection_placeholder={'Select a destination port'}
      aria_labelled_by={aria_labelled_by}
    />
  );
};

export default PortLocationsDropDown;
