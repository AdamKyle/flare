import React, { ChangeEvent } from "react";
import FetchUpgradableKingdomsAjax from "../ajax/fetch-upgradable-kingdoms-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "../event-listeners/capital-city-building-upgrade-repair-table-event-definition";
import CapitalCityBuildingUpgradeRepairTableEvent from "../event-listeners/capital-city-building-upgrade-repair-table-event";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import BuildingsToUpgradeSectionProps from "./types/buildings-to-upgrade-section-props";
import BuildingsToUpgradeSectionState from "./types/buildings-to-upgrade-section-state";
import Pagination from "./components/pagination";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import Kingdom from "./deffinitions/kingdom";
import BuildingToUpgradeService from "./services/building-to-upgrade-service";
import OpenKingdomCardForBuildingManagement from "./partials/open-kingdom-card-for-building-management";
import PrimaryButton from "../../ui/buttons/primary-button";

const MAX_ITEMS_PER_PAGE = 10;

export default class BuildingsToUpgradeSection extends React.Component<
    BuildingsToUpgradeSectionProps,
    BuildingsToUpgradeSectionState
> {
    private fetchUpgradableKingdomsAjax: FetchUpgradableKingdomsAjax;
    private updateBuildingTable: CapitalCityBuildingUpgradeRepairTableEventDefinition;
    private readonly buildingToUpgradeService: BuildingToUpgradeService;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            processing_request: false,
            success_message: null,
            error_message: null,
            building_data: [],
            filtered_building_data: [],
            open_kingdom_ids: new Set(),
            sort_direction: "asc",
            search_query: "",
            building_queue: [],
            currentPage: 1,
            itemsPerPage: MAX_ITEMS_PER_PAGE,
        };

        this.fetchUpgradableKingdomsAjax = serviceContainer().fetch(
            FetchUpgradableKingdomsAjax,
        );
        this.updateBuildingTable =
            serviceContainer().fetch<CapitalCityBuildingUpgradeRepairTableEventDefinition>(
                CapitalCityBuildingUpgradeRepairTableEvent,
            );
        this.buildingToUpgradeService = serviceContainer().fetch(
            BuildingToUpgradeService,
        );

        this.buildingToUpgradeService.setComponent(this);

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

    componentDidUpdate(prevProps: any, prevState: any) {
        if (prevState.building_data !== this.state.building_data) {
            this.buildingToUpgradeService.updateFilteredBuildingData();
        }
    }

    resetFilters() {
        this.setState(
            {
                search_query: "",
                sort_direction: "asc", // reset to default sorting
            },
            () => {
                this.buildingToUpgradeService.updateFilteredBuildingData(); // apply the reset
            },
        );
    }

    resetQueue() {
        this.setState({ building_queue: [] });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="md:p-4">
                {this.state.processing_request ? <LoadingProgressBar /> : null}

                {this.state.success_message !== null ? (
                    <SuccessAlert>{this.state.success_message}</SuccessAlert>
                ) : null}

                {this.state.error_message !== null ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}

                <input
                    type="text"
                    value={this.state.search_query}
                    onChange={(e: ChangeEvent<HTMLInputElement>) =>
                        this.buildingToUpgradeService.handleSearchChange(e)
                    }
                    placeholder="Search by kingdom name, map name, or building name"
                    className="w-full my-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-700 dark:placeholder-gray-300"
                    aria-label="Search by kingdom name, map name, or building name"
                />

                <div className="flex space-x-4 mb-4">
                    <PrimaryOutlineButton
                        on_click={() =>
                            this.buildingToUpgradeService.sortBuildings()
                        }
                        button_label={
                            <>
                                Sort by Building Level
                                <i
                                    className={`fas fa-arrow-${this.state.sort_direction === "asc" ? "up" : "down"} ml-2`}
                                />
                            </>
                        }
                    />

                    <DangerOutlineButton
                        on_click={() => this.resetFilters()}
                        button_label="Reset Form"
                    />

                    {this.state.building_queue.length > 0 && (
                        <>
                            <DangerOutlineButton
                                on_click={() => this.resetQueue()}
                                button_label={"Reset Queue"}
                            />
                            <SuccessOutlineButton
                                on_click={() =>
                                    this.buildingToUpgradeService.sendOrders()
                                }
                                button_label="Send Orders"
                            />
                        </>
                    )}
                </div>

                <div className="mb-4 text-gray-700 dark:text-gray-300">
                    Kingdom Count: {this.state.filtered_building_data.length} /{" "}
                    {this.state.building_data.length}
                </div>

                <div className="mb-4 text-center">
                    <PrimaryButton
                        button_label={
                            this.state.building_queue.length > 0
                                ? "Remove all from queue"
                                : "Queue All"
                        }
                        on_click={this.buildingToUpgradeService.toggleQueueAllBuildingsForAllKingdoms.bind(
                            this.buildingToUpgradeService,
                        )}
                        additional_css={"w-full"}
                    />
                </div>

                {this.buildingToUpgradeService
                    .getPaginatedData()
                    .map((kingdom: Kingdom) => (
                        <div
                            key={kingdom.kingdom_id}
                            className="bg-gray-100 dark:bg-gray-700 shadow-md rounded-lg overflow-hidden mb-4"
                        >
                            <div
                                className="p-4 flex justify-between items-center cursor-pointer"
                                onClick={() =>
                                    this.buildingToUpgradeService.toggleDetails(
                                        kingdom.kingdom_id,
                                    )
                                }
                            >
                                <div>
                                    <h2 className="text-xl font-bold dark:text-white">
                                        {kingdom.kingdom_name}
                                    </h2>
                                    <p className="text-gray-500 dark:text-gray-400">
                                        {kingdom.map_name}
                                    </p>

                                    {kingdom.buildings.some((building) =>
                                        this.buildingToUpgradeService.hasBuildingInQueue(
                                            kingdom,
                                            building,
                                        ),
                                    ) && (
                                        <p className="text-gray-600 dark:text-gray-500 mt-2">
                                            <strong>Buildings in Queue</strong>:{" "}
                                            {kingdom.buildings
                                                .filter((building) =>
                                                    this.buildingToUpgradeService.hasBuildingInQueue(
                                                        kingdom,
                                                        building,
                                                    ),
                                                )
                                                .map((building) =>
                                                    this.props.repair
                                                        ? `${building.name} (to be repaired)`
                                                        : `${building.name} (to level: ${building.level + 1})`,
                                                )
                                                .join(", ")}
                                        </p>
                                    )}
                                </div>
                                <i
                                    className={`fas fa-chevron-${this.state.open_kingdom_ids.has(kingdom.kingdom_id) ? "down" : "up"} text-gray-500 dark:text-gray-400`}
                                ></i>
                            </div>

                            {this.state.open_kingdom_ids.has(
                                kingdom.kingdom_id,
                            ) && (
                                <OpenKingdomCardForBuildingManagement
                                    building_queue={this.state.building_queue}
                                    has_building_in_queue={this.buildingToUpgradeService.hasBuildingInQueue.bind(
                                        this.buildingToUpgradeService,
                                    )}
                                    kingdom={kingdom}
                                    toggle_queue_all_buildings={this.buildingToUpgradeService.toggleQueueAllBuildings.bind(
                                        this.buildingToUpgradeService,
                                    )}
                                    toggle_building_queue={this.buildingToUpgradeService.toggleBuildingQueue.bind(
                                        this.buildingToUpgradeService,
                                    )}
                                />
                            )}
                        </div>
                    ))}
                <Pagination
                    on_page_change={this.buildingToUpgradeService.handlePageChange.bind(
                        this.buildingToUpgradeService,
                    )}
                    current_page={this.state.currentPage}
                    items_per_page={MAX_ITEMS_PER_PAGE}
                    total_items={this.state.filtered_building_data.length}
                />
            </div>
        );
    }
}
