import { QuestKind } from '../../enums/quest-kind';

export interface QuestTreeNodeIdentityDefinition {
  id: number;
  name: string;
}

export default interface QuestTreeNodeDefinition {
  id: number;
  name: string;
  kind: QuestKind;
  parent_quest_id: number | null;
  required_quest_id: number | null;
  required_quest_chain_ids: number[];
  npc: QuestTreeNodeIdentityDefinition | null;
  game_map: QuestTreeNodeIdentityDefinition | null;
  raid: QuestTreeNodeIdentityDefinition | null;
  only_for_event: number | null;
  children: QuestTreeNodeDefinition[];
}
