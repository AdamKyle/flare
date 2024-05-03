import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import KingdomResourceTransfer from "../kingdom-resource-transfer";
import { AxiosError, AxiosResponse } from "axios";

interface ResourceRequestParams {
    kingdom_requesting: number;
    kingdom_requesting_from: number;
    amount_of_resources: number;
    use_air_ship: boolean;
    type_of_resource: "wood" | "clay" | "stone" | "iron" | "steel" | "all";
}

@injectable()
export default class KingdomResourceTransferAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchKingdomsToTransferFrom(
        component: KingdomResourceTransfer,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdoms/" +
                    kingdomId +
                    "/" +
                    characterId +
                    "/resource-transfer-request",
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        kingdoms: result.data.kingdoms,
                        can_go_forward: result.data.kingdoms.length >= 2,
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

    public requestResources(
        component: KingdomResourceTransfer,
        params: ResourceRequestParams,
        characterId: number,
    ): void {
        this.ajax
            .setRoute("kingdom/" + characterId + "/send-request-for-resources")
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        requesting: false,
                        kingdoms: result.data.kingdoms,
                        can_go_forward: result.data.kingdoms.length >= 2,
                        success_message: result.data.message,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        requesting: false,
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
