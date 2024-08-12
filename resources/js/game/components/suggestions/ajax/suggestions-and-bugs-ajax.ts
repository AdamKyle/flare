import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import SuggestionsAndBugs from "../suggestions-and-bugs";

interface SuggestionsAndBugsAjaxParams {
    title: string;
    type: string;
    platform: string;
    description: string;
    files: File[] | [];
}

@injectable()
export default class SuggestionsAndBugsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public submitFeedback(
        component: SuggestionsAndBugs,
        characterId: number,
        params: SuggestionsAndBugsAjaxParams,
    ): void {
        const formParams = new FormData();

        formParams.set("title", params.title);
        formParams.set("type", params.type);
        formParams.set("platform", params.platform);
        formParams.set("description", params.description);

        params.files.forEach((file: File, index: number) => {
            formParams.append(`files[${index}]`, file);
        });

        this.ajax
            .setRoute("suggestions-and-bugs/" + characterId)
            .setParameters(formParams)
            .setAdditionalHeaders({
                "content-type": "multipart/form-data",
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        success_message: result.data.message,
                        processing_submission: false,
                        title: "",
                        type: "",
                        platform: "",
                        description: "",
                        files: [],
                        should_reset_markdown_element: true,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        processing_submission: false,
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
