import GemAbilityFormDefinition from '../api/definitions/gem-ability-form-definition';

export default interface GemAbilityFormContentProps {
  gem_ability_id: number | null;
  on_saved: (gem_ability: GemAbilityFormDefinition) => void;
  on_cancel: () => void;
}
