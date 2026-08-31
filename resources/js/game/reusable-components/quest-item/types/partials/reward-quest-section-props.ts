import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from '../quest-item-factual-definition';

export default interface RewardQuestsSectionProps {
  item: QuestItemFactualDefinition;
  showSeparator: boolean;
  navigation: QuestItemFactualNavigationDefinition;
}
