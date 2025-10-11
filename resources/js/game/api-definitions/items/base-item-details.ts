import { InventoryItemTypes } from '../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

export interface BaseItemDetails {
  affix_count: number;
  max_holy_stacks: number;
  holy_stacks_applied: number;
  holy_stacks_total_stat_increase: number;
  is_cosmic: boolean;
  is_mythic: boolean;
  is_unique: boolean;
  usable: boolean;
  holy_level: number | null;
  damages_kingdoms: boolean;
  name: string;
  description: string;
  type: InventoryItemTypes;
  cost: number;
  item_id: number;
  slot_id?: number | null;
}
