export default interface BaseGemDetails {
  slot_id: number;
  name: string;
  tier: number;
  amount: number;
  weak_against: string;
  strong_against: string;
  element_atoned_to: string;
  element_atoned_to_amount: number;
}
