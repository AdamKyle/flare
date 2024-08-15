import Section from "../deffinitions/section";

export default interface SurveyBuilderState {
    title: string;
    description: string;
    sections: Section[];
    showPreview: boolean;
    loading: boolean;
    processing: boolean;
    success_message: string | null;
    error_message: string | null;
}
