import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import SendRequestConfirmationModal from "../capital-city/modals/send-request-confirmation-modal";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";

@injectable()
export default class ProcessUpgradeBuildingsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public sendBuildingRequests(
        component: BuildingsToUpgradeSection,
        characterId: number,
        kingdomId: number,
        params: any,
    ): void {
        let requestType = "upgrade";

        if (component.props.repair) {
            requestType = "repair";
        }

        this.ajax
            .setRoute(
                "kingdom/capital-city/upgrade-building-requests/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                request_data: params,
                request_type: requestType,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            processing_request: false,
                            success_message: result.data.message,
                        },
                        () => {
                            component.resetFilters();
                            component.resetQueue();
                        },
                    );
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
