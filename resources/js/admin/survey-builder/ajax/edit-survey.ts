import { inject, injectable } from "tsyringe";
import Ajax from "../../../game/lib/ajax/ajax";
import AjaxInterface from "../../../game/lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import SurveyBuilder from "../survey-builder";

@injectable()
export default class EditSurvey {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchSurvey(component: SurveyBuilder) {
        if (!component.props.survey_id) {
            return;
        }

        component.setState({
            loading: true,
        });

        this.ajax
            .setRoute("admin/fetch-survey/" + component.props.survey_id)
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            loading: false,
                            sections: result.data.sections,
                            title: result.data.title,
                            description: result.data.description,
                        },
                        () => {},
                    );
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

    public saveSurvey(component: SurveyBuilder) {
        this.ajax
            .setRoute("admin/save-survey/" + component.props.survey_id)
            .setParameters({
                title: component.state.title,
                description: component.state.description,
                sections: component.state.sections,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            processing: false,
                            success_message: result.data.message,
                            sections: result.data.sections,
                            title: result.data.title,
                            description: result.data.description,
                        },
                        () => {},
                    );
                },
                (error: AxiosError) => {
                    component.setState({
                        processing: false,
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
