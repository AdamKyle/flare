export type GuideQuestShowRowValue =
  string | number | boolean | string[] | null;

export interface GuideQuestShowRowDefinition {
  label: string;
  value: GuideQuestShowRowValue;
}

export default interface GuideQuestShowSectionDefinition {
  title: string;
  rows: GuideQuestShowRowDefinition[];
}
