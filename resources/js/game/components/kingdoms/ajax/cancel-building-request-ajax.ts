import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import BuildingQueuesTable from "../capital-city/building-queues-table";
import SendBuildingUpgradeCancellationRequestModal from "../capital-city/modals/send-building-upgrade-cancellation-request-modal";

@injectable()
export default class CancelBuildingRequestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public cancelBuildingRequest(
        component: SendBuildingUpgradeCancellationRequestModal,
        characterId: number,
        kingdomId: number,
        queueId: number,
        deleteWholeQueue: boolean,
        buildingId?: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/cancel-building-request/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                delete_queue: deleteWholeQueue,
                building_id: buildingId,
                capital_city_building_queue_id: queueId,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        success_message: result.data.success_message,
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
