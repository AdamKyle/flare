export interface AttachedGemDefinition {
  id: number;
  tier: number;
  name: string;
  primary_atonement_name: string;
  secondary_atonement_name: string;
  tertiary_atonement_name: string;
  primary_atonement_amount: number;
  secondary_atonement_amount: number;
  tertiary_atonement_amount: number;
  weak_against: string;
  strong_against: string;
  element_atoned_to: string;
  element_atoned_to_amount: number;
}

export interface ItemSocketDataDefinition {
  item_sockets: number;
  current_used_slots: number;
  item_name: string;
}

export type GemToAttachDefinition = AttachedGemDefinition;

export interface ReplacementAttributeDifferencesDefinition {
  gem_you_have_id: number;
  tier: number;
  name: string;
  primary_atonement_type?: string;
  primary_atonement_amount?: number;
  secondary_atonement_type?: string;
  secondary_atonement_amount?: number;
  tertiary_atonement_type?: string;
  tertiary_atonement_amount?: number;
}

export interface ElementalAtonementDefinition {
  atonements: { [elementName: string]: number };
  elemental_damage: { name: string; amount: number };
}

export interface ReplacementAtonementResultDefinition {
  name_to_replace: string;
  gem_id: number;
  data: ElementalAtonementDefinition;
}

export default interface GemComparisonApiResponseDefinition {
  attached_gems: AttachedGemDefinition[];
  socket_data: ItemSocketDataDefinition;
  has_gems_on_item: boolean;
  gem_to_attach: GemToAttachDefinition;
  when_replacing: ReplacementAttributeDifferencesDefinition[];
  if_replacing_atonements?: ReplacementAtonementResultDefinition[];
  if_replaced?: [];
  original_atonement?: ElementalAtonementDefinition;
  message?: string;
}
