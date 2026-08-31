import { QuestItemFactualNavigationDefinition } from '../../../../game/reusable-components/quest-item/types/quest-item-factual-definition';
import ItemDetailDefinition from '../../api/definitions/item-detail-definition';

export default interface AdminItemPresentationProps {
  item: ItemDetailDefinition;
  navigation?: QuestItemFactualNavigationDefinition;
}
