import QuestItemFactualDefinition, {
  QuestItemFactualNavigationDefinition,
} from '../quest-item-factual-definition';

export default interface MonsterDropsSectionProps {
  item: QuestItemFactualDefinition;
  showSeparator: boolean;
  navigation: QuestItemFactualNavigationDefinition;
}
