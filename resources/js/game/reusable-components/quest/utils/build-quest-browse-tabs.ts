import QuestBrowseContent from '../components/quest-browse-content';
import {
  QUEST_BROWSE_TAB_LABELS,
  QuestBrowseTab,
} from '../enums/quest-browse-tab';
import QuestBrowseContentProps from '../types/quest-browse-content-props';

import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

type SharedContentProps = Omit<QuestBrowseContentProps, 'active_tab'>;

export const buildQuestBrowseTabs = (
  content: SharedContentProps
): Readonly<
  TabTupleFromProps<
    [QuestBrowseContentProps, QuestBrowseContentProps, QuestBrowseContentProps]
  >
> => {
  const baseTab = {
    label: QUEST_BROWSE_TAB_LABELS[QuestBrowseTab.BASE],
    component: QuestBrowseContent,
    props: { ...content, active_tab: QuestBrowseTab.BASE },
  } as const;

  const oneOffsTab = {
    label: QUEST_BROWSE_TAB_LABELS[QuestBrowseTab.ONE_OFFS],
    component: QuestBrowseContent,
    props: { ...content, active_tab: QuestBrowseTab.ONE_OFFS },
  } as const;

  const raidTab = {
    label: QUEST_BROWSE_TAB_LABELS[QuestBrowseTab.RAID],
    component: QuestBrowseContent,
    props: { ...content, active_tab: QuestBrowseTab.RAID },
  } as const;

  return [baseTab, oneOffsTab, raidTab] as const;
};
