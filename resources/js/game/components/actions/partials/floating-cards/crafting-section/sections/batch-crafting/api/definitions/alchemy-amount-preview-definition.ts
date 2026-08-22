export interface AlchemyBagCapacityDefinition {
  current: number;
  max: number;
  remaining: number;
}

export default interface AlchemyAmountPreviewDefinition {
  item_id: number | null;
  item_name: string | null;
  requested_amount: number;
  gold_dust_cost_each: number;
  shards_cost_each: number;
  total_gold_dust_cost: number;
  total_shards_cost: number;
  gold_dust_available: number;
  shards_available: number;
  disposition: string | null;
  listing_price: number | null;
  alchemy_bag_capacity: AlchemyBagCapacityDefinition | null;
  blockers: string[];
}
