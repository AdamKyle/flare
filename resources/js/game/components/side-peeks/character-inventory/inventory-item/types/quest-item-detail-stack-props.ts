import QuestItemFactualDefinition from '../../../../../reusable-components/quest-item/types/quest-item-factual-definition';

export default interface QuestItemDetailStackProps {
  quest_item: QuestItemFactualDefinition;
  on_close: () => void;
}
