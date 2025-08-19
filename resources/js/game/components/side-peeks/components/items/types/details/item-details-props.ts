import BaseQuestItemDefinition from '../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import BaseInventoryItemDefinition from '../../../../character-inventory/api-definitions/base-inventory-item-definition';

export default interface ItemDetailsProps {
  item: BaseInventoryItemDefinition | BaseQuestItemDefinition;
}
