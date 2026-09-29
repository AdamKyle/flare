export enum GuideQuestApiUrls {
  CURRENT = '/character/guide-quest/{user}',
  HAND_IN = '/guide-quests/hand-in/{user}/{guideQuest}',
  WALKTHROUGH = '/character/{user}/guide-quests/{guideQuest}/walkthrough',
  ACKNOWLEDGE_ATTENTION = '/character/guide-quest/{user}/acknowledge-attention',
}
