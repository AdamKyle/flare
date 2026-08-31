import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from './quest-item-factual-definition';

export default interface QuestItemFactualPresentationProps {
  item: QuestItemFactualDefinition;
  title_class_name?: string;
  navigation?: QuestItemFactualNavigationDefinition;
}
