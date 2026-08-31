import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestTreeNodeDefinition from '../../../../../game/reusable-components/quest/api/definitions/quest-tree-node-definition';

export default interface UseQuestTreeDefinition {
  quests: QuestTreeNodeDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
