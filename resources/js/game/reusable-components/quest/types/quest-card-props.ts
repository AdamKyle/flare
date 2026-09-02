import { QuestTreeState } from '../enums/quest-tree-state';

export default interface QuestCardProps {
  quest_id: number;
  name: string;
  kind_label?: string;
  state?: QuestTreeState;
  npc_name?: string | null;
  child_count?: number;
  context_label?: string;
  on_open_quest: (id: number) => void;
}
