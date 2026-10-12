import React, { ReactNode } from 'react';

import { BackpackFiltersProps } from './types/backpack-filters-props';
import { BackpackEquipmentFilter } from '../enums/backpack-equipment-filter';
import {
  armourSubtypeOptions,
  equipmentOptions,
  rarityOptions,
  weaponSubtypeOptions,
} from '../utils/backpack-filter-options';

import Dropdown from 'ui/drop-down/drop-down';

const BackpackFilters = ({
  equipmentFilter,
  equipmentSubtype,
  rarityFilter,
  selectEquipmentFilter,
  clearEquipmentFilter,
  selectEquipmentSubtype,
  clearEquipmentSubtype,
  selectRarityFilter,
  clearRarityFilter,
}: BackpackFiltersProps): ReactNode => {
  const selectedEquipment = equipmentOptions.find(
    (item) => item.value === equipmentFilter
  );
  const subtypeOptions =
    equipmentFilter === BackpackEquipmentFilter.WEAPON
      ? weaponSubtypeOptions
      : armourSubtypeOptions;
  const selectedSubtype = subtypeOptions.find(
    (item) => item.value === equipmentSubtype
  );
  const selectedRarity = rarityOptions.find(
    (item) => item.value === rarityFilter
  );

  return (
    <div className="grid gap-2 px-4 pt-2 sm:grid-cols-2">
      <Dropdown
        items={equipmentOptions}
        pre_selected_item={selectedEquipment}
        on_select={selectEquipmentFilter}
        on_clear={clearEquipmentFilter}
        selection_placeholder="Equipment"
      />
      {equipmentFilter === BackpackEquipmentFilter.WEAPON && (
        <Dropdown
          items={weaponSubtypeOptions}
          pre_selected_item={selectedSubtype}
          on_select={selectEquipmentSubtype}
          on_clear={clearEquipmentSubtype}
          selection_placeholder="Weapon Type"
        />
      )}
      {equipmentFilter === BackpackEquipmentFilter.ARMOUR && (
        <Dropdown
          items={armourSubtypeOptions}
          pre_selected_item={selectedSubtype}
          on_select={selectEquipmentSubtype}
          on_clear={clearEquipmentSubtype}
          selection_placeholder="Armour Type"
        />
      )}
      <Dropdown
        items={rarityOptions}
        pre_selected_item={selectedRarity}
        on_select={selectRarityFilter}
        on_clear={clearRarityFilter}
        selection_placeholder="Rarity / Enchantment"
      />
    </div>
  );
};

export default BackpackFilters;
