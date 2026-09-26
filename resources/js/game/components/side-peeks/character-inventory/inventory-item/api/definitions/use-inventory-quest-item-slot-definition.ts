import BaseQuestItemDefinition from '../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export default interface UseInventoryQuestItemSlotDefinition {
  quest_item: BaseQuestItemDefinition | null;
  loading: boolean;
  error: string | null;
}
