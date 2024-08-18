export default interface SurveyComponentProps {
    user_id: number;
    character_id: number;
    show_survey_button: (showSurvey: boolean) => void;
    open_survey: boolean;
    close_survey: () => void;
    set_success_message: (message: string | null) => void;
}
