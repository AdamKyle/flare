import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import BuildingQueuesTable from "../capital-city/building-queues-table";
import UnitRecruitment from "../capital-city/unit-recruitment";
import UnitQueuesTable from "../capital-city/unit-queues-table";

@injectable()
export default class FetchUnitQueuesAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchUnitQueueData(
        component: UnitQueuesTable,
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
