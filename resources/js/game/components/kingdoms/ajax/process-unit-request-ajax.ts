import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import SendUnitRecruitmentRequestModal from "../capital-city/modals/send-unit-recruitment-request-modal";

@injectable()
export default class ProcessUnitRequestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public processRequest(
        component: SendUnitRecruitmentRequestModal,
        characterId: number,
        kingdomId: number,
        params: any,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/recruit-unit-requests/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                request_data: params,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            loading: false,
                            success_message: result.data.message,
                        },
                        () => {
                            component.props.reset_request_form();
                        },
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
}
