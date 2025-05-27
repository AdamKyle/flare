import { isEmpty, isNil } from 'lodash';
import React from 'react';

import NpcKingdomsDropDownProps from './types/npc-kingdoms-drop-down-props';
import { LocationTypes } from '../enums/location-types';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const NpcKingdomDropDown = ({
  npc_kingdoms,
  on_select,
  on_clear,
  location_type_selected,
}: NpcKingdomsDropDownProps) => {
  if (isEmpty(npc_kingdoms)) {
    return null;
  }

  const kingdomChoices = npc_kingdoms.map((kingdom) => {
    return {
      label: `${kingdom.name} (${kingdom.x_position} / ${kingdom.y_position})`,
      value: kingdom.id,
    };
  });

  const handleSelection = (selectedKingdom: DropdownItem) => {
    on_select(selectedKingdom, LocationTypes.NPC_KINGDOM);
  };

  const shouldForceClear = () => {
    if (isNil(location_type_selected)) {
      return false;
    }

    return location_type_selected !== LocationTypes.NPC_KINGDOM;
  };

  const handleOnClear = () => {
    if (location_type_selected !== LocationTypes.NPC_KINGDOM) {
      return;
    }

    on_clear();
  };

  return (
    <Dropdown
      items={kingdomChoices}
      on_select={handleOnClear}
      on_clear={on_clear}
      selection_placeholder={'Select an NPC kingdom'}
      all_click_outside
      is_in_modal
      force_clear={shouldForceClear()}
    />
  );
};

export default NpcKingdomDropDown;
