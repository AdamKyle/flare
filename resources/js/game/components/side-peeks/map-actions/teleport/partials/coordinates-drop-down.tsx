import { isEmpty } from 'lodash';
import React from 'react';

import CoordinatesDropDownProps from './types/coordinates-drop-down-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const CoordinatesDropDown = ({ coordinates }: CoordinatesDropDownProps) => {
  if (isEmpty(coordinates)) {
    return null;
  }

  const coordinateOptions = coordinates.map((coordinate) => {
    return {
      label: coordinate.toString(),
      value: coordinate,
    };
  });

  const handleSelection = (selectedCoordinate: DropdownItem) => {
    console.log(selectedCoordinate);
  };

  return (
    <Dropdown
      items={coordinateOptions}
      on_select={handleSelection}
      selection_placeholder={'Select a coordinate'}
      all_click_outside
      is_in_modal
    />
  );
};

export default CoordinatesDropDown;
