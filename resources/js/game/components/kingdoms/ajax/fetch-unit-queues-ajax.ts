import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import UnitQueue from "../capital-city/partials/unit-management/unit-queue";

@injectable()
export default class FetchUnitQueuesAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) { }

    public fetchUnitQueueData(
        component: UnitQueue,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/unit-queues/" +
                characterId +
                "/" +
                kingdomId,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        unit_queues: result.data.unit_queues,
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
