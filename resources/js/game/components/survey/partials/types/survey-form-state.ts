import { SurveyInput } from "../deffinitions/survey-input";

export default interface SurveyFormState {
    current_section_index: number;
    all_sections_filled: boolean;
    survey_input: SurveyInput;
}
