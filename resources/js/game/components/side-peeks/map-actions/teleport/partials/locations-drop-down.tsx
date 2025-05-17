import { isEmpty } from 'lodash';
import React from 'react';

import LocationDropDownProps from './types/location-drop-down-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const LocationsDropDown = ({ locations }: LocationDropDownProps) => {
  if (isEmpty(locations)) {
    return null;
  }

  const locationChoices = locations.map((location) => {
    return {
      label: `${location.name} (${location.x_position} / ${location.y_position})`,
      value: location.id,
    };
  });

  const handleSelection = (selectedLocation: DropdownItem) => {
    console.log(selectedLocation);
  };

  return (
    <Dropdown
      items={locationChoices}
      on_select={handleSelection}
      selection_placeholder={'Select a location'}
      all_click_outside
      is_in_modal
    />
  );
};

export default LocationsDropDown;
