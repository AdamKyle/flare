import GuideQuestDefinition from './guide-quest-definition';

export interface GuideQuestRequirementProgressDefinition {
  quest_id: number;
  completed_requirements: string[];
}

export interface GuideQuestHandInDefinition {
  quest_id: number;
  can_hand_in: boolean;
}

export default interface GuideQuestResponseDefinition {
  quests: GuideQuestDefinition[];
  completed_requirements: GuideQuestRequirementProgressDefinition[];
  can_hand_in: GuideQuestHandInDefinition[];
  has_activity_attention: boolean;
  message?: string;
}
