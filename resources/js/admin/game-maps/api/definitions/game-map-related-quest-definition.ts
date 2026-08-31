import { QuestKind } from '../../../../game/reusable-components/quest/enums/quest-kind';

export interface GameMapRelatedQuestNpcDefinition {
  id: number;
  name: string;
}

export default interface GameMapRelatedQuestDefinition {
  id: number;
  name: string;
  kind: QuestKind;
  npc: GameMapRelatedQuestNpcDefinition | null;
}
