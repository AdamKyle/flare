export default interface EnchantingInventoryItemDefinition {
  id: number;
  item_id: number;
  item: { id: number; affix_name: string; name: string; affix_count: number };
}
