import GuideQuest from "../../components/definitions/guide-quest";

export interface RequiredBatchCraftedItemRequirement {
    requirement_index: number;
    source: string;
    item_id: number;
    required_amount: number;
    current_amount: number;
    must_be_enchanted: boolean;
    is_complete: boolean;
}

export interface CompletedGuideQuestRequirements {
    quest_id: number;
    completed_requirements: string[];
    required_batch_crafted_item_requirements: RequiredBatchCraftedItemRequirement[];
}

export default interface GuideQuestState {
    loading: boolean;
    action_loading: boolean;
    error_message: string | null;
    success_message: string | null;
    quest_data: GuideQuest[] | [];
    can_hand_in:
        | [
              {
                  quest_id: number;
                  can_hand_in: boolean;
              },
          ]
        | [];
    is_handing_in: boolean;
    completed_requirements: CompletedGuideQuestRequirements[];
    selected_quest_data_to_show: GuideQuest | null;
}
