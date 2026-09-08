import CharacterQuestReadinessDefinition from './character-quest-readiness-definition';
import QuestDetailDefinition from '../../../../../reusable-components/quest/api/definitions/quest-detail-definition';
import QuestItemOwnershipState from '../../../../side-peeks/components/items/enums/quest-item-ownership-state';

export default interface CharacterQuestDetailResponseDefinition {
  quest: QuestDetailDefinition;
  completed_quest_ids: number[];
  readiness: CharacterQuestReadinessDefinition;
  quest_item_ownership: Record<number, QuestItemOwnershipState>;
}
