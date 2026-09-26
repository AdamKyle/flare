import DelveQuestItemDefinition from '../../api/definitions/delve-quest-item-definition';

export default interface DelveQuestItemsSectionProps {
  quest_items: DelveQuestItemDefinition[];
  on_view_quest_item: (itemId: number) => void;
}
