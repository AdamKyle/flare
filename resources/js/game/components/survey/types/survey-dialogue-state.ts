export default interface SurveyDialogueState {
    loading: boolean;
    error_message: string | null;
    success_message: string | null;
    survey: {
        id: number;
        title: string;
        description: string;
        sections: any[];
    };
    section_inputs: {
        [index: number]: {
            [key: string]: {
                value: string | boolean | string[];
                type: string;
            };
        };
    };
    all_sections_filled: boolean;
    showCloseConfirmation: boolean;
    saving_survey: boolean;
}
