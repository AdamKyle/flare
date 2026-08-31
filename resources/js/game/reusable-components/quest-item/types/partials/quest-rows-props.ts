import { QuestIdentityDefinition } from '../quest-item-factual-definition';

export default interface QuestRowsProps {
  heading: string;
  quest: QuestIdentityDefinition;
  on_open_quest?: (id: number) => void;
  on_open_npc?: (id: number) => void;
  on_open_map?: (id: number) => void;
}
