import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from '../quest-item-factual-definition';

export default interface DropSectionProps {
  item: QuestItemFactualDefinition;
  showSeparator: boolean;
  navigation: QuestItemFactualNavigationDefinition;
}
