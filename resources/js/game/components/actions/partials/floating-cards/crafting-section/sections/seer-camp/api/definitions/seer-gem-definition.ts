import CharacterGemDefinition from '../../../../../../../../../api-definitions/gems/character-gem-definition';

export default interface SeerGemDefinition {
  slot_id: number;
  amount: number;
  gem: CharacterGemDefinition;
}
