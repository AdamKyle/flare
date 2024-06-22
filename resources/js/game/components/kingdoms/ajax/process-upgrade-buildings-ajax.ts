import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import SendRequestConfirmationModal from "../capital-city/modals/send-request-confirmation-modal";

@injectable()
export default class ProcessUpgradeBuildingsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchDetails(
        component: SendRequestConfirmationModal,
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
