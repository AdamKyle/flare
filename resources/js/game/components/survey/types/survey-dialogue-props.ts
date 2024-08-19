export default interface SurveyDialogueProps {
    survey_id: number;
    is_open: boolean;
    manage_modal: () => void;
    character_id: number;
    set_success_message: (message: string | null) => void;
}
