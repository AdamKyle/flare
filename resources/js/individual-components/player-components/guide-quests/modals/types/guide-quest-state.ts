import GuideQuest from "../../components/definitions/guide-quest";

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
    completed_requirements: string[] | [];
    selected_quest_data_to_show: GuideQuest | null;
}
