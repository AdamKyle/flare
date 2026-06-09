import CraftableItemDefinition from './craftable-item-definition';

export interface CraftingXpDefinition {
  current_xp: number;
  next_level_xp: number;
  skill_name: string;
  level: number;
}

export interface CraftingInventoryCountDefinition {
  current_count: number;
  max_inventory: number;
}

export default interface CraftingApiResponseDefinition {
  data: CraftableItemDefinition[];
  items: CraftableItemDefinition[];
  meta: {
    can_load_more: boolean;
    pagination: {
      count: number;
      current_page: number;
      links: Record<string, string | null>;
      per_page: number;
      total: number;
      total_pages: number;
    };
  };
  xp: CraftingXpDefinition;
  show_craft_for_npc: boolean;
  show_craft_for_event: boolean;
  inventory_count: CraftingInventoryCountDefinition;
  crafted_item?: boolean;
}
