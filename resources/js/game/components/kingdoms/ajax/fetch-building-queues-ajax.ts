import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import BuildingQueuesTable from "../capital-city/building-queues-table";

@injectable()
export default class FetchBuildingQueuesAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchQueueData(
        component: BuildingQueuesTable,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/building-queues/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        building_queues: result.data.building_queues,
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
