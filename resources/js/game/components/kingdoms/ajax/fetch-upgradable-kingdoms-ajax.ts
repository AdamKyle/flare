import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";

@injectable()
export default class FetchUpgradableKingdomsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchDetails(
        component: BuildingsToUpgradeSection,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/manage-buildings/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    let data = result.data;

                    if (component.props.repair) {
                        data = result.data.map(
                            (kingdom: {
                                kingdom_name: any;
                                kingdom_id: number;
                                buildings: any[];
                            }) => ({
                                kingdom_name: kingdom.kingdom_name,
                                kingdom_id: kingdom.kingdom_id,
                                buildings: kingdom.buildings.filter(
                                    (building) =>
                                        building.current_durability <
                                        building.max_durability,
                                ),
                            }),
                        );
                    }

                    component.compressArray(data, false);

                    component.setState({
                        loading: false,
                        building_data: data,
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
