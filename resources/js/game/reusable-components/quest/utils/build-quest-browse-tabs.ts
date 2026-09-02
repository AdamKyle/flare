import QuestBrowseContent from '../components/quest-browse-content';
import {
  QUEST_BROWSE_TAB_LABELS,
  QuestBrowseTab,
} from '../enums/quest-browse-tab';
import QuestBrowseContentProps from '../types/quest-browse-content-props';

import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

type SharedContentProps = Omit<QuestBrowseContentProps, 'active_tab'>;

/**
 * Build the fixed Base / One Offs / Raid primary Quest browse tabs for the
 * shared `PillTabs` component. All three tabs render the same shared
 * `QuestBrowseContent` with the same currently-fetched data, differing
 * only in which `QuestBrowseTab` they present; only the active tab's panel
 * is ever mounted by `PillTabs`, so only one Quest category's data is ever
 * requested at a time by the caller's data hook.
 *
 * @param  content  Currently-fetched Quest browse data and navigation, for the active category.
 * @return  The fixed 3-tuple of primary Quest browse tabs.
 */
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
