import BaseUsableItemDefinition from '../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface ModifiersSectionProps {
  item: BaseUsableItemDefinition;
  showSeparator: boolean;
  showTitleSeparator?: boolean;
}
