import React from "react";
import FetchUpgradableKingdomsAjax from "../ajax/fetch-upgradable-kingdoms-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "../event-listeners/capital-city-building-upgrade-repair-table-event-definition";
import CapitalCityBuildingUpgradeRepairTableEvent from "../event-listeners/capital-city-building-upgrade-repair-table-event";

export default class BuildingsToUpgradeSection extends React.Component<
    any,
    any
> {
    private fetchUpgradableKingdomsAjax: FetchUpgradableKingdomsAjax;
    private updateBuildingTable: CapitalCityBuildingUpgradeRepairTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            building_data: [],
            upgrade_queue: [],
        };

        this.fetchUpgradableKingdomsAjax = serviceContainer().fetch(
            FetchUpgradableKingdomsAjax,
        );

        this.updateBuildingTable =
            serviceContainer().fetch<CapitalCityBuildingUpgradeRepairTableEventDefinition>(
                CapitalCityBuildingUpgradeRepairTableEvent,
            );

        this.updateBuildingTable.initialize(this, this.props.user_id);
        this.updateBuildingTable.register();
    }

    componentDidMount() {
        this.fetchUpgradableKingdomsAjax.fetchDetails(
            this,
            this.props.kingdom.character_id,
            this.props.kingdom.id,
        );
        this.updateBuildingTable.listen();
    }

    toggle_details(kingdom_id: number) {
        this.setState((prevState: any) => {
            const new_open_kingdom_ids =
                prevState.open_kingdom_ids || new Set(); // Ensure open_kingdom_ids is always a Set
            if (new_open_kingdom_ids.has(kingdom_id)) {
                new_open_kingdom_ids.delete(kingdom_id);
            } else {
                new_open_kingdom_ids.add(kingdom_id);
            }
            return { open_kingdom_ids: new_open_kingdom_ids };
        });
    }

    render() {
        return (
            <div>
                {this.state.building_data.map((kingdom: any) => (
                    <div
                        key={kingdom.kingdom_id}
                        className="bg-white dark:bg-gray-700 shadow-md rounded-lg overflow-hidden mb-4"
                    >
                        {/* Card Header */}
                        <div
                            className="p-4 flex justify-between items-center cursor-pointer"
                            onClick={() =>
                                this.toggle_details(kingdom.kingdom_id)
                            }
                        >
                            <div>
                                <h2 className="text-xl font-bold dark:text-white">
                                    {kingdom.kingdom_name}
                                </h2>
                                <p className="text-gray-500 dark:text-gray-400">
                                    {kingdom.map_name}
                                </p>
                            </div>
                            <i
                                className={`fas fa-chevron-${this.state.open_kingdom_ids && this.state.open_kingdom_ids.has(kingdom.kingdom_id) ? "down" : "up"} text-gray-500 dark:text-gray-400`}
                            ></i>
                        </div>

                        {/* Card Details */}
                        {this.state.open_kingdom_ids &&
                            this.state.open_kingdom_ids.has(
                                kingdom.kingdom_id,
                            ) && (
                                <div className="bg-gray-100 dark:bg-gray-600 p-4">
                                    {kingdom.buildings.map((building: any) => (
                                        <div
                                            key={building.id}
                                            className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                                        >
                                            <h3 className="text-lg font-semibold dark:text-white">
                                                {building.name}
                                            </h3>
                                            <p className="text-gray-700 dark:text-gray-300">
                                                {building.description}
                                            </p>
                                            <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                <p className="flex justify-between">
                                                    <strong className="text-gray-800 dark:text-gray-200">
                                                        Level:
                                                    </strong>
                                                    <span>
                                                        {building.level}
                                                    </span>
                                                </p>
                                                <p className="flex justify-between">
                                                    <strong className="text-gray-800 dark:text-gray-200">
                                                        Defense:
                                                    </strong>
                                                    <span>
                                                        {
                                                            building.current_defence
                                                        }{" "}
                                                        / {building.max_defence}
                                                    </span>
                                                </p>
                                                <p className="flex justify-between">
                                                    <strong className="text-gray-800 dark:text-gray-200">
                                                        Durability:
                                                    </strong>
                                                    <span>
                                                        {
                                                            building.current_durability
                                                        }{" "}
                                                        /{" "}
                                                        {
                                                            building.max_durability
                                                        }
                                                    </span>
                                                </p>
                                                <p className="flex justify-between">
                                                    <strong className="text-gray-800 dark:text-gray-200">
                                                        Cost:
                                                    </strong>
                                                    <span>
                                                        <strong>Wood</strong>:{" "}
                                                        {building.wood_cost},{" "}
                                                        <strong>Stone</strong>:{" "}
                                                        {building.stone_cost},{" "}
                                                        <strong>Clay</strong>:{" "}
                                                        {building.clay_cost},{" "}
                                                        <strong>Iron</strong>:{" "}
                                                        {building.iron_cost}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                    </div>
                ))}
            </div>
        );
    }
}
