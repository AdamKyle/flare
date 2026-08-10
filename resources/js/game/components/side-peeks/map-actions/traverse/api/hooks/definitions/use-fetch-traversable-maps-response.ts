import BaseQuestItemDefinition from '../../../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export default interface UseFetchTraversableMapsResponse {
  id: number;
  name: string;
  map_required_item: BaseQuestItemDefinition | null;
}
