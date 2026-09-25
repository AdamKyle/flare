export default interface MarketListingSummaryDefinition {
  id: number;
  character_id: number;
  item_id: number;
  name: string;
  listed_price: number;
  is_locked: boolean;
  character_name: string;
  type: string;
  unique: boolean;
  listed_at: string;
}
