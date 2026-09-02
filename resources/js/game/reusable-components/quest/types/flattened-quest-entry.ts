import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';

export default interface FlattenedQuestEntry {
  quest: QuestTreeNodeDefinition;
  depth: number;
  parent_name: string | null;
  root_name: string | null;
}
