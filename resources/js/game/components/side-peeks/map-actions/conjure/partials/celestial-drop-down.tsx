import React from 'react';

import CelestialOptionDefinition from '../api/definitions/celestial-option-definition';
import CelestialDropDownProps from './types/celestial-drop-down-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const CelestialDropDown = ({
  celestials,
  on_select,
  on_clear,
  aria_labelled_by,
}: CelestialDropDownProps) => {
  const celestialChoices: DropdownItem[] = celestials.map((celestial) => {
    return {
      label: celestial.name,
      value: celestial.id,
    };
  });

  const handleSelection = (selectedItem: DropdownItem) => {
    const selectedCelestial = celestials.find(
      (celestial: CelestialOptionDefinition) =>
        celestial.id === selectedItem.value
    );

    if (!selectedCelestial) {
      return;
    }

    on_select(selectedCelestial);
  };

  return (
    <Dropdown
      items={celestialChoices}
      on_select={handleSelection}
      on_clear={on_clear}
      selection_placeholder={'Select a celestial'}
      aria_labelled_by={aria_labelled_by}
    />
  );
};

export default CelestialDropDown;
