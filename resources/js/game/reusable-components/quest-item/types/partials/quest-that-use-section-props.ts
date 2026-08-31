import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from '../quest-item-factual-definition';

export default interface QuestsThatUseSectionProps {
  item: QuestItemFactualDefinition;
  showSeparator: boolean;
  navigation: QuestItemFactualNavigationDefinition;
}
