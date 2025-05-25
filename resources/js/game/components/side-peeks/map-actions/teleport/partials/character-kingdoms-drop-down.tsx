import { isEmpty, isNil } from 'lodash';
import React from 'react';

import CharacterKingdomsDropDownProps from './types/character-kingdoms-drop-down-props';
import { LocationTypes } from '../enums/location-types';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const CharacterKingdomsDropDown = ({
  character_kingdoms,
  on_select,
  location_type_selected,
}: CharacterKingdomsDropDownProps) => {
  if (isEmpty(character_kingdoms)) {
    return null;
  }

  const kingdomChoices = character_kingdoms.map((kingdom) => {
    return {
      label: `${kingdom.name} (${kingdom.x_position} / ${kingdom.y_position})`,
      value: kingdom.id,
    };
  });

  const handleSelection = (selectedKingdom: DropdownItem) => {
    on_select(selectedKingdom, LocationTypes.MY_KINGDOM);
  };

  const shouldForceClear = () => {
    if (isNil(location_type_selected)) {
      return false;
    }

    return location_type_selected !== LocationTypes.MY_KINGDOM;
  };

  return (
    <Dropdown
      items={kingdomChoices}
      on_select={handleSelection}
      selection_placeholder={'Select one of your kingdoms'}
      all_click_outside
      is_in_modal
      force_clear={shouldForceClear()}
    />
  );
};

export default CharacterKingdomsDropDown;
