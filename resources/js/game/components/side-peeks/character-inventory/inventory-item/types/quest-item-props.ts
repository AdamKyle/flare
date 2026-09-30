import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from '../../../../../reusable-components/quest-item/types/quest-item-factual-definition';

export default interface QuestItemProps {
  quest_item: QuestItemFactualDefinition;
  navigation?: QuestItemFactualNavigationDefinition;
}
