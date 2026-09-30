import DuplicateGemAbilityDefinition from '../../../../../api-definitions/gems/duplicate-gem-ability-definition';

export default interface ItemDetailsBodyProps {
  item_id: number;
  duplicate_gem_abilities?: DuplicateGemAbilityDefinition[];
}
