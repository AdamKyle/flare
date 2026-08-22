export interface HolyOilsTargetPreviewDefinition {
  target_slot_id: number;
  item_id: number;
  item_name: string;
  eligible: boolean;
  current_holy_stacks: number;
  max_holy_stacks: number;
  planned_applications: number;
  resulting_holy_stacks: number;
  gold_dust_cost: number;
}

export interface HolyOilsSelectedItemsPreviewDefinition {
  target_count: number;
  oil_units_available: number;
  planned_application_count: number;
  total_gold_dust_cost: number;
  gold_dust_available: number;
  targets: HolyOilsTargetPreviewDefinition[];
  disposition: string;
  listing_price: number | null;
  blockers: string[];
}

export interface HolyOilsSetPreviewDefinition extends HolyOilsSelectedItemsPreviewDefinition {
  set_id: number | null;
  set_name: string | null;
}
