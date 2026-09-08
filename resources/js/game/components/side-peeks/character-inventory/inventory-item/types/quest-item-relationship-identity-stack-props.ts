import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export type QuestItemRelationshipIdentityKind = 'location' | 'map' | 'monster';

export default interface QuestItemRelationshipIdentityStackProps {
  quest_item: BaseQuestItemDefinition;
  kind: QuestItemRelationshipIdentityKind;
  id: number;
  on_close: () => void;
  on_open_map: (id: number) => void;
}
