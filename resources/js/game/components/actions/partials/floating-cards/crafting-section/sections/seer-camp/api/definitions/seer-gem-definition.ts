import CondensedGemDetails from '../../../../../../../../../api-definitions/items/condensed-gem-details';

export default interface SeerGemDefinition {
  slot_id: number;
  amount: number;
  gem: CondensedGemDetails;
}
