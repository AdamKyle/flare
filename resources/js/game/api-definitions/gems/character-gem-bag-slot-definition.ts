import CharacterGemDefinition from './character-gem-definition';

export default interface CharacterGemBagSlotDefinition extends CharacterGemDefinition {
  slot_id: number;
  amount: number;
}
