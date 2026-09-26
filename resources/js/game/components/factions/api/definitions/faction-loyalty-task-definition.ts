export default interface FactionLoyaltyTaskDefinition {
  type: string;
  item_name?: string;
  item_id?: number;
  monster_name?: string;
  monster_id?: number;
  required_amount: number;
  current_amount: number;
}
