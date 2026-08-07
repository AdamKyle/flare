import BaseGemDetails from '../../../../../../api-definitions/items/base-gem-details';

export default interface UseCharacterGemBagDefinition {
  openGemBag: (initialGem?: BaseGemDetails) => void;
}
