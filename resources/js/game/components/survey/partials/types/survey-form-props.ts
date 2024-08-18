import Section from "../../../../../admin/survey-builder/deffinitions/section";
import { SurveyInput } from "../deffinitions/survey-input";

export default interface SurveyFormProps {
    is_open: boolean;
    survey: {
        title: string;
        description: string;
        sections: Section[];
    };
    section_inputs: SurveyInput
    all_sections_filled: boolean;
    loading: boolean;
    submitSurvey: () => void;
    handleClose: () => void;
    error_message: string | null;
    success_message: string | null;
    retrieve_input: (input: SurveyInput) => void;
    saving_survey: boolean;
}
