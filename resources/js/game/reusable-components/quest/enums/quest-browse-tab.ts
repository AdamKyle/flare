import { QuestKind } from './quest-kind';

export enum QuestBrowseTab {
  BASE = 'base',
  ONE_OFFS = 'one_offs',
  RAID = 'raid',
}

export const QUEST_BROWSE_TAB_LABELS: Record<QuestBrowseTab, string> = {
  [QuestBrowseTab.BASE]: 'Quest Tree',
  [QuestBrowseTab.ONE_OFFS]: 'One Offs',
  [QuestBrowseTab.RAID]: 'Raid Quests',
};

/**
 * Presentation-only mapping from a Quest browse tab to the actual, backend
 * authoritative `QuestKind` it requests. Never persisted or sent to the
 * backend as its own value.
 */
export const QUEST_BROWSE_TAB_KIND: Record<QuestBrowseTab, QuestKind> = {
  [QuestBrowseTab.BASE]: QuestKind.CHAIN,
  [QuestBrowseTab.ONE_OFFS]: QuestKind.ONE_OFF,
  [QuestBrowseTab.RAID]: QuestKind.RAID,
};

export const QUEST_BROWSE_TABS_ORDER: QuestBrowseTab[] = [
  QuestBrowseTab.BASE,
  QuestBrowseTab.ONE_OFFS,
  QuestBrowseTab.RAID,
];

/**
 * Category-aware empty-state copy for the Quest browser, avoiding a
 * generic "no results" message now that Kind filtering is a fixed tab
 * rather than a user-controlled dropdown. The first tab is the normal
 * parent/child story Quest hierarchy, not a Raid tree.
 */
export const QUEST_BROWSE_TAB_EMPTY_LABELS: Record<QuestBrowseTab, string> = {
  [QuestBrowseTab.BASE]: 'No Quests are available for this plane.',
  [QuestBrowseTab.ONE_OFFS]: 'No One Off Quests are available for this plane.',
  [QuestBrowseTab.RAID]: 'No Raid Quests are available for this plane.',
};
