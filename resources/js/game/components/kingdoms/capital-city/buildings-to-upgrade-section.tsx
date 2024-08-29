import React from "react";
import FetchUpgradableKingdomsAjax from "../ajax/fetch-upgradable-kingdoms-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "../event-listeners/capital-city-building-upgrade-repair-table-event-definition";
import CapitalCityBuildingUpgradeRepairTableEvent from "../event-listeners/capital-city-building-upgrade-repair-table-event";
import debounce from "lodash/debounce";

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
            filtered_building_data: [],
            open_kingdom_ids: new Set(),
            sort_direction: "asc",
            search_query: "",
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

    componentDidUpdate(prevProps: any, prevState: any) {
        if (prevState.building_data !== this.state.building_data) {
            this.updateFilteredBuildingData();
        }
    }

    toggleDetails(kingdomId: number) {
        this.setState((prevState: any) => {
            const newOpenKingdomIds = new Set(prevState.open_kingdom_ids);
            if (newOpenKingdomIds.has(kingdomId)) {
                newOpenKingdomIds.delete(kingdomId);
            } else {
                newOpenKingdomIds.add(kingdomId);
            }
            return { open_kingdom_ids: newOpenKingdomIds };
        });
    }

    sortBuildings() {
        const filteredBuildingData = this.state.filtered_building_data;
        const sortDirection = this.state.sort_direction;
        const newDirection = sortDirection === "asc" ? "desc" : "asc";

        const sortedData = filteredBuildingData.map((kingdom: any) => ({
            ...kingdom,
            buildings: kingdom.buildings.sort((a: any, b: any) =>
                newDirection === "asc" ? a.level - b.level : b.level - a.level,
            ),
        }));

        this.setState({
            filtered_building_data: sortedData,
            sort_direction: newDirection,
        });
    }

    updateFilteredBuildingData() {
        const searchTerm = this.state.search_query?.toLowerCase() || "";

        const filteredBuildingData = this.state.building_data
            .map((kingdom: any) => {
                const kingdomNameMatches =
                    kingdom.kingdom_name.toLowerCase() === searchTerm;
                const mapNameMatches = kingdom.map_name
                    .toLowerCase()
                    .includes(searchTerm);
                const matchingBuildings = kingdom.buildings.filter(
                    (building: any) => {
                        const buildingName = building.name
                            ? building.name.toLowerCase()
                            : "";
                        return buildingName.includes(searchTerm);
                    },
                );

                if (
                    matchingBuildings.length > 0 ||
                    kingdomNameMatches ||
                    mapNameMatches
                ) {
                    this.state.open_kingdom_ids.add(kingdom.kingdom_id);
                } else {
                    this.state.open_kingdom_ids.delete(kingdom.kingdom_id);
                }

                return {
                    ...kingdom,
                    buildings:
                        kingdomNameMatches || mapNameMatches
                            ? kingdom.buildings
                            : matchingBuildings,
                    matchingBuildings,
                };
            })
            .filter((kingdom: any) => {
                if (searchTerm !== "") {
                    return (
                        kingdom.kingdom_name.toLowerCase() === searchTerm ||
                        kingdom.map_name.toLowerCase().includes(searchTerm) ||
                        kingdom.matchingBuildings.length > 0
                    );
                }
                return true;
            });

        // Apply sorting after filtering
        const sortedData = filteredBuildingData.map((kingdom: any) => ({
            ...kingdom,
            buildings: kingdom.buildings.sort((a: any, b: any) =>
                this.state.sort_direction === "asc"
                    ? a.level - b.level
                    : b.level - a.level,
            ),
        }));

        this.setState({ filtered_building_data: sortedData });
    }

    handleSearchChange(event: React.ChangeEvent<HTMLInputElement>) {
        const searchTerm = event.target.value;
        this.setState({ search_query: searchTerm });

        this.debouncedUpdateFilteredData();
    }

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredBuildingData();
    }, 300);

    resetFilters = () => {
        this.setState(
            {
                search_query: "",
                sort_direction: "asc", // reset to default sorting
            },
            () => {
                this.updateFilteredBuildingData(); // apply the reset
            },
        );
    };

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="md:p-4">
                <input
                    type="text"
                    value={this.state.search_query}
                    onChange={(e) => this.handleSearchChange(e)}
                    placeholder="Search by kingdom name, map name, or building name"
                    className="w-full mb-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search by kingdom name, map name, or building name"
                />

                <div className="flex space-x-4 mb-4">
                    <button
                        onClick={() => this.sortBuildings()}
                        className="w-1/2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                    >
                        Sort by Building Level
                        <i
                            className={`fas fa-arrow-${this.state.sort_direction === "asc" ? "up" : "down"} ml-2`}
                        />
                    </button>

                    <button
                        onClick={this.resetFilters}
                        className="w-1/2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50"
                    >
                        Reset Form
                    </button>
                </div>

                <div className="mb-4 text-gray-700 dark:text-gray-300">
                    Kingdom Count: {this.state.filtered_building_data.length} /{" "}
                    {this.state.building_data.length}
                </div>

                {this.state.filtered_building_data.map((kingdom: any) => (
                    <div
                        key={kingdom.kingdom_id}
                        className="bg-white dark:bg-gray-700 shadow-md rounded-lg overflow-hidden mb-4"
                    >
                        <div
                            className="p-4 flex justify-between items-center cursor-pointer"
                            onClick={() =>
                                this.toggleDetails(kingdom.kingdom_id)
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
                                className={`fas fa-chevron-${this.state.open_kingdom_ids.has(kingdom.kingdom_id) ? "down" : "up"} text-gray-500 dark:text-gray-400`}
                            ></i>
                        </div>

                        {this.state.open_kingdom_ids.has(
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
                                                    {building.level} /{" "}
                                                    {building.max_level}
                                                </span>
                                            </p>
                                            <p className="flex justify-between">
                                                <strong className="text-gray-800 dark:text-gray-200">
                                                    Defense:
                                                </strong>
                                                <span>
                                                    {building.current_defence} /{" "}
                                                    {building.max_defence}
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
                                                    / {building.max_durability}
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
