import DuplicateGemAbilityDefinition from '../../../../../api-definitions/gems/duplicate-gem-ability-definition';

export default interface ItemDetailsStackProps {
  item_id: number;
  on_close: () => void;
  duplicate_gem_abilities?: DuplicateGemAbilityDefinition[];
}
