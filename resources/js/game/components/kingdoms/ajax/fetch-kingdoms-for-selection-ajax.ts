import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";

@injectable()
export default class FetchKingdomsForSelectionAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchDetails(
        component: UnitRecruitment,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/manage-units/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    let data = result.data;

                    component.setState({
                        loading: false,
                        kingdoms_for_selection: data.kingdoms,
                        filtered_unit_recruitment_data: data.kingdoms,
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
