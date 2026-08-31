import { QuestScreens } from './quest-screen-constants';

export type QuestListScreenProps = Record<string, never>;

export interface QuestShowScreenProps {
  quest_id: number;
}

export interface QuestFormScreenProps {
  quest_id: number | null;
  parent_quest_id: number | null;
}

export interface QuestScreenPropsMap {
  [QuestScreens.LIST]: QuestListScreenProps;
  [QuestScreens.SHOW]: QuestShowScreenProps;
  [QuestScreens.FORM]: QuestFormScreenProps;
}

export type QuestScreenName = keyof QuestScreenPropsMap;
