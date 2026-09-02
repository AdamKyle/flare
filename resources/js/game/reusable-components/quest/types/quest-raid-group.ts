import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';

export default interface QuestRaidGroup {
  raid_id: number | null;
  raid_name: string;
  quests: QuestTreeNodeDefinition[];
}
