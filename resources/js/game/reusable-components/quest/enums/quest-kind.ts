export enum QuestKind {
  CHAIN = 'chain',
  ONE_OFF = 'one_off',
  RAID = 'raid',
}

export const QUEST_KIND_LABELS: Record<QuestKind, string> = {
  [QuestKind.CHAIN]: 'Quest Chain',
  [QuestKind.ONE_OFF]: 'One Off Quests',
  [QuestKind.RAID]: 'Raid Quests',
};

export const isQuestKind = (value: unknown): value is QuestKind =>
  typeof value === 'string' &&
  Object.values(QuestKind).some((kind) => kind === value);
