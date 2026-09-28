import CharacterGemBagSlotDefinition from '../../../../../api-definitions/gems/character-gem-bag-slot-definition';

export default interface GemSlotProps {
  gem_slot: CharacterGemBagSlotDefinition;
  on_view_gem: (slotId: number) => void;
}
