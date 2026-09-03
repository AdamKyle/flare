import { QuestTreeNavigationDefinition } from './quest-node-props';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';

import TreeMobileMode from 'ui/tree/enums/tree-mobile-mode';

export default interface RaidGroupPanelProps {
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
  navigation?: QuestTreeNavigationDefinition;
  tree_mobile_mode?: TreeMobileMode;
  accessibility_label: string;
}
