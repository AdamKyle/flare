import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestTreeNodeDefinition from '../../../../../game/reusable-components/quest/api/definitions/quest-tree-node-definition';

export default interface UsePublicQuestTreeDefinition {
  quests: QuestTreeNodeDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  query_key: string | null;
}
