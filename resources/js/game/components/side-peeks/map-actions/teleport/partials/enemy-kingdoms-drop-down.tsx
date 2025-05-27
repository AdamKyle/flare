import { isEmpty, isNil } from 'lodash';
import React from 'react';

import EnemyKingdomsDropDownProps from './types/enemy-kingdoms-drop-down-props';
import { LocationTypes } from '../enums/location-types';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const EnemyKingdomsDropDown = ({
  enemy_kingdoms,
  location_type_selected,
  on_select,
  on_clear,
}: EnemyKingdomsDropDownProps) => {
  if (isEmpty(enemy_kingdoms)) {
    return null;
  }

  const kingdomChoices = enemy_kingdoms.map((kingdom) => {
    return {
      label: `${kingdom.name} (${kingdom.x_position} / ${kingdom.y_position})`,
      value: kingdom.id,
    };
  });

  const handleSelection = (selectedKingdom: DropdownItem) => {
    on_select(selectedKingdom, LocationTypes.ENEMY_KINGDOM);
  };

  const shouldForceClear = () => {
    if (isNil(location_type_selected)) {
      return false;
    }

    return location_type_selected !== LocationTypes.ENEMY_KINGDOM;
  };

  const handleOnClear = () => {
    if (location_type_selected !== LocationTypes.LOCATION) {
      return;
    }

    on_clear();
  };

  return (
    <Dropdown
      items={kingdomChoices}
      on_select={handleSelection}
      on_clear={handleOnClear}
      selection_placeholder={'Select an enemy kingdom'}
      all_click_outside
      is_in_modal
      force_clear={shouldForceClear()}
    />
  );
};

export default EnemyKingdomsDropDown;
