import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import SurveyDialogue from "../survey-dialogue";

interface SuggestionsAndBugsAjaxParams {
    title: string;
    type: string;
    platform: string;
    description: string;
    files: File[] | [];
}

@injectable()
export default class SurveyAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public getSurvey(component: SurveyDialogue, survey_id: number): void {
        this.ajax.setRoute("survey/" + survey_id).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                component.setState({
                    survey: {
                        title: result.data.title,
                        description: result.data.description,
                        sections: result.data.sections,
                    },
                    loading: false,
                });
            },
            (error: AxiosError) => {
                component.setState({
                    loading: false,
                });

                if (typeof error.response !== "undefined") {
                    const response: AxiosResponse = error.response;

                    component.setState({
                        error_message: response.data.message,
                    });
                }
            },
        );
    }
}
