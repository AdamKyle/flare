import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import KingdomResourceTransfer from "../kingdom-resource-transfer";
import { AxiosError, AxiosResponse } from "axios";

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
                "/kingdoms/" +
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
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        component.setState({
                            error_message: error.response.data.message,
                        });
                    }
                },
            );
    }
}
