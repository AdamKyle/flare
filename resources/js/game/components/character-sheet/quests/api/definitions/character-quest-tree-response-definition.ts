import QuestTreeNodeDefinition from '../../../../../reusable-components/quest/api/definitions/quest-tree-node-definition';

export default interface CharacterQuestTreeResponseDefinition {
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
}
