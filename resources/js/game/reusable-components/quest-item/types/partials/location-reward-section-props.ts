import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from '../quest-item-factual-definition';

export default interface RewardLocationsSectionProps {
  item: QuestItemFactualDefinition;
  showSeparator: boolean;
  navigation: QuestItemFactualNavigationDefinition;
}
