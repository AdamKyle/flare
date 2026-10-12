import { useEffect, useState } from 'react';

import UseBackpackFiltersDefinition from './definitions/use-backpack-filters-definition';
import UseBackpackFiltersParams from './definitions/use-backpack-filters-params';
import { InventoryItemTypes } from '../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { BackpackEquipmentFilter } from '../enums/backpack-equipment-filter';
import { BackpackRarityFilter } from '../enums/backpack-rarity-filter';
import BackpackFilterDefinition from '../types/backpack-filter-definition';
import {
  resolveEquipmentFilter,
  resolveEquipmentItemTypes,
  resolveItemType,
  resolveRarityFilter,
} from '../utils/backpack-filter-options';

export const useBackpackFilters = ({
  initial_filters: initialFilters,
  setFilters,
}: UseBackpackFiltersParams): UseBackpackFiltersDefinition => {
  const [equipmentFilter, setEquipmentFilter] =
    useState<BackpackEquipmentFilter | null>(null);
  const [equipmentSubtype, setEquipmentSubtype] =
    useState<InventoryItemTypes | null>(null);
  const [rarityFilter, setRarityFilter] = useState<BackpackRarityFilter | null>(
    initialFilters?.rarity ?? null
  );
  const [useInitialItemTypes, setUseInitialItemTypes] = useState(
    initialFilters?.item_types !== undefined
  );

  useEffect(() => {
    const itemTypes = useInitialItemTypes
      ? initialFilters?.item_types
      : resolveEquipmentItemTypes(equipmentFilter, equipmentSubtype);
    const filters: BackpackFilterDefinition = {};

    if (itemTypes !== undefined && itemTypes.length > 0) {
      filters.item_types = itemTypes;
    }

    if (rarityFilter !== null) {
      filters.rarity = rarityFilter;
    }

    setFilters(filters);
  }, [
    equipmentFilter,
    equipmentSubtype,
    initialFilters?.item_types,
    rarityFilter,
    setFilters,
    useInitialItemTypes,
  ]);

  const selectEquipmentFilter = (item: { value: string | number }): void => {
    setUseInitialItemTypes(false);
    setEquipmentFilter(resolveEquipmentFilter(item.value));
    setEquipmentSubtype(null);
  };

  const clearEquipmentFilter = (): void => {
    setUseInitialItemTypes(false);
    setEquipmentFilter(null);
    setEquipmentSubtype(null);
  };

  const selectEquipmentSubtype = (item: { value: string | number }): void => {
    setEquipmentSubtype(resolveItemType(item.value));
  };

  const selectRarityFilter = (item: { value: string | number }): void => {
    setRarityFilter(resolveRarityFilter(item.value));
  };

  return {
    equipmentFilter,
    equipmentSubtype,
    rarityFilter,
    selectEquipmentFilter,
    clearEquipmentFilter,
    selectEquipmentSubtype,
    clearEquipmentSubtype: () => setEquipmentSubtype(null),
    selectRarityFilter,
    clearRarityFilter: () => setRarityFilter(null),
  };
};
