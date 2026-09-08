import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestTreeNodeDefinition from '../../../../../../reusable-components/quest/api/definitions/quest-tree-node-definition';

export default interface UseCharacterQuestTreeDefinition {
  quests: QuestTreeNodeDefinition[];
  completedQuestIds: number[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  queryKey: string | null;
  refresh: () => void;
  replaceCompletedQuestIds: (ids: number[]) => void;
}
