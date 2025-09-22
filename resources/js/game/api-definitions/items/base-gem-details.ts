export default interface BaseGemDetails {
  slot_id: number;
  name: string;
  tier: number;
  amount: number;
  weak_against: string;
  strong_against: string;
  element_atoned_to: string;
  element_atoned_to_amount: number;
  primary_atonement_type: string;
  primary_atonement_amount: number;
  secondary_atonement_type: string;
  secondary_atonement_amount: number;
  tertiary_atonement_type: string;
  tertiary_atonement_amount: number;
}
