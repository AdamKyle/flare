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

                    // Filter based on repair status
                    if (component.props.repair) {
                        data = data
                            .map(
                                (kingdom: {
                                    map_name: any;
                                    kingdom_name: any;
                                    kingdom_id: number;
                                    buildings: any[];
                                }) => ({
                                    kingdom_name: kingdom.kingdom_name,
                                    kingdom_id: kingdom.kingdom_id,
                                    map_name: kingdom.map_name,
                                    buildings: kingdom.buildings
                                        .filter(
                                            (building) =>
                                                building.current_durability <
                                                building.max_durability,
                                        )
                                        .sort((a, b) => a.level - b.level), // Sort buildings by level (lowest to highest)
                                }),
                            )
                            .filter(
                                (kingdom: any) => kingdom.buildings.length > 0, // Keep only kingdoms with buildings needing repair
                            );
                    } else {
                        data = data.map(
                            (kingdom: {
                                map_name: any;
                                kingdom_name: any;
                                kingdom_id: number;
                                buildings: any[];
                            }) => ({
                                kingdom_name: kingdom.kingdom_name,
                                map_name: kingdom.map_name,
                                kingdom_id: kingdom.kingdom_id,
                                buildings: kingdom.buildings
                                    .filter(
                                        (building) =>
                                            building.current_durability >=
                                            building.max_durability,
                                    )
                                    .sort((a, b) => a.level - b.level), // Sort buildings by level (lowest to highest)
                            }),
                        );
                    }

                    // Set the state with the filtered and sorted data
                    component.setState({
                        loading: false,
                        building_data: data,
                    });

                    // Update the filtered data
                    component.updateFilteredBuildingData();
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
