import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestTreeNodeDefinition from '../../../../../reusable-components/quest/api/definitions/quest-tree-node-definition';
import { QuestBrowseTab } from '../../../../../reusable-components/quest/enums/quest-browse-tab';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface CharacterQuestBrowsePanelProps {
  game_maps: DropdownItem[];
  selected_game_map_id: number | null;
  selected_game_map_name: string | null;
  active_tab: QuestBrowseTab;
  show_raid_tab: boolean;
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  on_select_game_map: (id: number) => void;
  on_active_tab_change: (tab: QuestBrowseTab) => void;
  on_open_quest: (id: number) => void;
}
