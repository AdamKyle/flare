import { GuideQuestScreens } from './guide-quest-screen-constants';

export type GuideQuestListScreenProps = Record<string, never>;

export interface GuideQuestShowScreenProps {
  guide_quest_id: number;
}

export interface GuideQuestScreenPropsMap {
  [GuideQuestScreens.LIST]: GuideQuestListScreenProps;
  [GuideQuestScreens.SHOW]: GuideQuestShowScreenProps;
}

export type GuideQuestScreenName = keyof GuideQuestScreenPropsMap;
