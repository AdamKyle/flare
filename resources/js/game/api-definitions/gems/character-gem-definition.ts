import CharacterGemModifierDefinition from './character-gem-modifier-definition';

export default interface CharacterGemDefinition {
  id: number;
  name: string;
  tier: number;
  domain: string;
  modifiers: CharacterGemModifierDefinition[];
}
