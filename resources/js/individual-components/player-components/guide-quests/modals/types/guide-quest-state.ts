export default interface GuideQuestState {
    loading: boolean;
    action_loading: boolean;
    error_message: string | null;
    success_message: string | null;
    quest_data: null | any;
    can_hand_in: boolean;
    is_handing_in: boolean;
    completed_requirements: string[]|[],
}
