import { isEmpty } from 'lodash';
import React from 'react';

import EnemyKingdomsDropDownProps from './types/enemy-kingdoms-drop-down-props';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const EnemyKingdomsDropDown = ({
  enemy_kingdoms,
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
    console.log(selectedKingdom);
  };

  return (
    <Dropdown
      items={kingdomChoices}
      on_select={handleSelection}
      selection_placeholder={'Select an enemy kingdom'}
      all_click_outside
      is_in_modal
    />
  );
};

export default EnemyKingdomsDropDown;
