import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { QuestTreeNavigationDefinition } from './quest-node-props';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import { QuestBrowseTab } from '../enums/quest-browse-tab';

import TreeMobileMode from 'ui/tree/enums/tree-mobile-mode';

export default interface QuestBrowseContentProps {
  active_tab: QuestBrowseTab;
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  navigation?: QuestTreeNavigationDefinition;
  tree_mobile_mode?: TreeMobileMode;
  selected_game_map_name: string | null;
}
