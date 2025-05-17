import { isEmpty } from 'lodash';
import React from 'react';

import CharacterKingdomsDropDownProps from './types/character-kingdoms-drop-down-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const CharacterKingdomsDropDown = ({
  character_kingdoms,
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
    console.log(selectedKingdom);
  };

  return (
    <Dropdown
      items={kingdomChoices}
      on_select={handleSelection}
      selection_placeholder={'Select one of your kingdoms'}
      all_click_outside
      is_in_modal
    />
  );
};

export default CharacterKingdomsDropDown;
