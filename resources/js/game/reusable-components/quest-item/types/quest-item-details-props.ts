import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from './quest-item-factual-definition';

export default interface QuestItemDetailsProps {
  item: QuestItemFactualDefinition;
  navigation?: QuestItemFactualNavigationDefinition;
}
