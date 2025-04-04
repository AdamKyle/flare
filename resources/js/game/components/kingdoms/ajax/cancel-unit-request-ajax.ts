import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import BuildingsInQueue from "../capital-city/buildings-in-queue";
import SendBuildingUpgradeCancellationRequestModal from "../capital-city/modals/send-building-upgrade-cancellation-request-modal";
import SendUnitRequestCancellationRequestModal from "../capital-city/modals/send-unit-request-cancellation-request-modal";
import { CancellationType } from "../capital-city/enums/cancellation-type";

@injectable()
export default class CancelUnitRequestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public cancelUnitRequest(
        component: SendUnitRequestCancellationRequestModal,
        characterId: number,
        kingdomId: number,
        queueId: number,
        unitName: string | null,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/cancel-unit-request/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                queue_id: queueId,
                unit_name: unitName,
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
