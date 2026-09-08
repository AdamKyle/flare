import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { QuestItemFactualNavigationDefinition } from '../../../../../reusable-components/quest-item/types/quest-item-factual-definition';

export default interface QuestItemProps {
  quest_item: BaseQuestItemDefinition;
  navigation?: QuestItemFactualNavigationDefinition;
}
