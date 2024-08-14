import { inject, injectable } from "tsyringe";
import Ajax from "../../../game/lib/ajax/ajax";
import AjaxInterface from "../../../game/lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import SurveyBuilder from "../survey-builder";

@injectable()
export default class CreateNewSurvey {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public createNewSurvey(component: SurveyBuilder) {
        this.ajax
            .setRoute("admin/create-new-survey")
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
                            sections: [],
                            title: '',
                            description: '',
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
