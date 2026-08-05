export default interface CondensedGemDetails {
  id: number;
  name: string;
  tier: number;
  domain: string;
  primary_atonement_type: string;
  primary_atonement_amount: number;
  secondary_atonement_type: string;
  secondary_atonement_amount: number;
  tertiary_atonement_type: string;
  tertiary_atonement_amount: number;
  gold_gain: number;
  gold_dust_gain: number;
  shards_gain: number;
  copper_coin_gain: number;
  crafting_skill_bonus: number;
  item_drop_chance_increase: number;
  unique_item_drop_chance_increase: number;
  mythic_item_drop_chance_increase: number;
  cosmic_item_drop_chance_increase: number;
}
