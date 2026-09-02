import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { QuestTreeNavigationDefinition } from './quest-node-props';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import { QuestBrowseTab } from '../enums/quest-browse-tab';

export default interface QuestBrowseContentProps {
  active_tab: QuestBrowseTab;
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  navigation?: QuestTreeNavigationDefinition;
}
