import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";

@injectable()
export default class ProcessUnitRequestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public processRequest(
        component: UnitRecruitment,
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
                    component.setState({
                        processing_request: false,
                        success_message: result.data.message,
                        unit_queue: [],
                        bulk_input_values: {},
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        processing_request: false,
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
