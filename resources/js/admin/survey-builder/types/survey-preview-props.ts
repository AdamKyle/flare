import Section from "../deffinitions/section";

export default interface SurveyPreviewProps {
    sections: Section[];
    survey_title: string;
    survey_description: string;
}
