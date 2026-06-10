import CraftingFiltersDefinition from './crafting-filters-definition';

export default interface CraftItemRequestDefinition {
  item_to_craft: number;
  type: string;
  craft_for_npc: boolean;
  craft_for_event: boolean;
  per_page: number;
  page: number;
  search_text: string;
  filters: CraftingFiltersDefinition;
}
