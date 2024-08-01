import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import SendUnitRequestCancellationRequestModal from "../capital-city/modals/send-unit-request-cancellation-request-modal";

@injectable()
export default class CancelUnitRequestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public cancelUnitRequest(
        component: SendUnitRequestCancellationRequestModal,
        characterId: number,
        kingdomId: number,
        queueId: number,
        deleteWholeQueue: boolean,
        unitId?: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/cancel-unit-request/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                request_data: {
                    delete_queue: deleteWholeQueue,
                    unit_id: unitId,
                    capital_city_unit_queue_id: queueId,
                },
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        success_message: result.data.message,
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
