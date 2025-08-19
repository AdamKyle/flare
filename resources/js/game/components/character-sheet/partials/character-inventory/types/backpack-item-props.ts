import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface BackpackItemProps {
  item: BaseInventoryItemDefinition | BaseQuestItemDefinition;
  on_click?: (
    item: BaseInventoryItemDefinition | BaseQuestItemDefinition
  ) => void;
}
