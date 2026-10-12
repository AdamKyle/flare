import GuideQuestDefinition from './guide-quest-definition';

export interface GuideQuestBatchCraftedItemRequirementProgressDefinition {
  requirement_index: number;
  source: string;
  item_id: number | null;
  required_amount: number | null;
  current_amount: number;
  must_be_enchanted: boolean;
  is_complete: boolean;
}

type BatchCraftedItemRequirementProgress =
  GuideQuestBatchCraftedItemRequirementProgressDefinition;

export interface GuideQuestRequirementProgressDefinition {
  quest_id: number;
  completed_requirements: string[];
  required_batch_crafted_item_requirements: BatchCraftedItemRequirementProgress[];
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
