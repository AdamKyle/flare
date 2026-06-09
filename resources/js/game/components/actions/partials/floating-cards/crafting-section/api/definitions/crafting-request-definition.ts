export interface CraftingFiltersDefinition {
  armour_type?: string;
}

export interface FetchCraftingItemsRequestDefinition {
  crafting_type?: string;
  per_page: number;
  page: number;
  search_text: string;
  filters: CraftingFiltersDefinition;
}

export default interface CraftingRequestDefinition {
  item_to_craft: number;
  type: string;
  craft_for_npc: boolean;
  craft_for_event: boolean;
  per_page: number;
  page: number;
  search_text: string;
  filters: CraftingFiltersDefinition;
}
