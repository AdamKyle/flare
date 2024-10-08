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

    public getSurvey(component: SurveyDialogue, surveyId: number): void {
        this.ajax.setRoute("survey/" + surveyId).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                component.setState({
                    survey: {
                        id: result.data.id,
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

    public saveSurvey(
        component: SurveyDialogue,
        surveyId: number,
        characterId: number,
        params: any,
    ): void {
        this.ajax
            .setRoute(`survey/submit/${surveyId}/${characterId}`)
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        saving_survey: false,
                    });

                    component.confirmCloseWithSuccessMessage(
                        result.data.message,
                    );
                },
                (error: AxiosError) => {
                    component.setState({
                        saving_survey: false,
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
