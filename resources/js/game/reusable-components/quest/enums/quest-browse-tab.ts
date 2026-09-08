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

export const QUEST_BROWSE_TAB_EMPTY_LABELS: Record<QuestBrowseTab, string> = {
  [QuestBrowseTab.BASE]: 'No Quests are available for this plane.',
  [QuestBrowseTab.ONE_OFFS]: 'No One Off Quests are available for this plane.',
  [QuestBrowseTab.RAID]: 'No Raid Quests are available for this plane.',
};
