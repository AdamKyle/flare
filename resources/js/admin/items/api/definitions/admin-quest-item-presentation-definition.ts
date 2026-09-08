import QuestItemFactualDefinition from '../../../../game/reusable-components/quest-item/types/quest-item-factual-definition';

export default interface AdminQuestItemPresentationDefinition extends QuestItemFactualDefinition {
  item_id: number;
  can_drop: boolean;
  usable: boolean;
  craft_only: boolean;
}
