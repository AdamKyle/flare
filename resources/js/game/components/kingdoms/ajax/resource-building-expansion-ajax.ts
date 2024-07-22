import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import ResourceBuildingExpansion from "../buildings/resource-building-expansion";

@injectable()
export default class ResourceBuildingExpansionAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchResourceExpansionData(
        component: ResourceBuildingExpansion,
        characterId: number,
        buildingId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/building-expansion/details/" +
                    buildingId +
                    "/" +
                    characterId,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        expansion_details: result.data.expansion_details,
                        time_remaining_for_expansion: result.data.time_left,
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

    public expandBuilding(
        component: ResourceBuildingExpansion,
        characterId: number,
        buildingId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/building-expansion/expand/" +
                    buildingId +
                    "/" +
                    characterId,
            )
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        expanding: false,
                        success_message: result.data.message,
                        time_remaining_for_expansion: result.data.time_left,
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
