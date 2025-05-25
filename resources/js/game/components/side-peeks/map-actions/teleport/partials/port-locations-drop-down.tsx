import { isEmpty, isNil } from 'lodash';
import React from 'react';

import LocationDropDownProps from './types/location-drop-down-props';
import { LocationTypes } from '../enums/location-types';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const PortLocationsDropDown = ({
  locations,
  location_type_selected,
  on_select,
}: LocationDropDownProps) => {
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
    on_select(selectedLocation, LocationTypes.PORT_LOCATION);
  };

  const shouldForceClear = () => {
    if (isNil(location_type_selected)) {
      return false;
    }

    return location_type_selected !== LocationTypes.PORT_LOCATION;
  };

  return (
    <Dropdown
      items={locationChoices}
      on_select={handleSelection}
      selection_placeholder={'Select a location'}
      force_clear={shouldForceClear()}
      all_click_outside
      is_in_modal
    />
  );
};

export default PortLocationsDropDown;
