import CharacterGemBagSlotDefinition from '../../../../../../api-definitions/gems/character-gem-bag-slot-definition';

export default interface UseCharacterGemBagDefinition {
  openGemBag: (initialGem?: CharacterGemBagSlotDefinition) => void;
}
