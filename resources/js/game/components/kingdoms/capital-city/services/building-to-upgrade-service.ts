import BuildingsToUpgradeSection from "../buildings-to-upgrade-section";
import BuildingsToUpgradeSectionState from "../types/buildings-to-upgrade-section-state";
import Kingdom from "../deffinitions/kingdom-with-buildings";
import Building from "../deffinitions/building";
import { BuildingQueue } from "../../deffinitions/building-queue";
import debounce from "lodash/debounce";
import ProcessUpgradeBuildingsAjax from "../../ajax/process-upgrade-buildings-ajax";
import { inject, injectable } from "tsyringe";

@injectable()
export default class BuildingToUpgradeService {
    private component?: BuildingsToUpgradeSection;

    constructor(
        @inject(ProcessUpgradeBuildingsAjax)
        private processBuildingRequest: ProcessUpgradeBuildingsAjax,
    ) {}

    public setComponent(component: BuildingsToUpgradeSection) {
        this.component = component;
    }

    toggleDetails(kingdomId: number) {
        if (!this.component) {
            return;
        }

        this.component.setState((prevState: BuildingsToUpgradeSectionState) => {
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
        if (!this.component) {
            return;
        }

        const filteredBuildingData =
            this.component.state.filtered_building_data;
        const sortDirection = this.component.state.sort_direction;
        const newDirection = sortDirection === "asc" ? "desc" : "asc";

        const sortedData = filteredBuildingData.map((kingdom: any) => ({
            ...kingdom,
            buildings: kingdom.buildings.sort((a: any, b: any) =>
                newDirection === "asc" ? a.level - b.level : b.level - a.level,
            ),
        }));

        this.component.setState({
            filtered_building_data: sortedData,
            sort_direction: newDirection,
        });
    }

    updateFilteredBuildingData() {
        if (!this.component) {
            return;
        }

        const searchTerm = this.component.state.search_query
            .toLowerCase()
            .trim();

        const openKingdomIds = new Set<number>();

        let filteredBuildingData = this.component.state.building_data.filter(
            (kingdom: Kingdom) => {
                return (
                    (kingdom.kingdom_name.toLowerCase().includes(searchTerm) ||
                        kingdom.map_name.toLowerCase().includes(searchTerm)) &&
                    kingdom.buildings.length > 0
                );
            },
        );

        if (filteredBuildingData.length <= 0 && searchTerm.length > 0) {
            filteredBuildingData = this.component.state.building_data
                .map((kingdom: Kingdom | null) => {
                    if (kingdom === null) {
                        return null;
                    }

                    const matchingBuildings = kingdom.buildings.filter(
                        (building: Building) =>
                            building.name.toLowerCase().includes(searchTerm),
                    );

                    if (matchingBuildings.length > 0) {
                        openKingdomIds.add(kingdom.kingdom_id);

                        return {
                            ...kingdom,
                            buildings: matchingBuildings,
                        };
                    }

                    return null;
                })
                .filter((kingdom: Kingdom | null) => kingdom !== null);
        }

        const sortedData = filteredBuildingData.map((kingdom: Kingdom) => ({
            ...kingdom,
            buildings: kingdom.buildings.sort((a: Building, b: Building) => {
                if (!this.component) {
                    return a.level;
                }

                return this.component.state.sort_direction === "asc"
                    ? a.level - b.level
                    : b.level - a.level;
            }),
        }));

        this.component.setState({
            filtered_building_data: sortedData,
            open_kingdom_ids: openKingdomIds,
        });
    }

    handleSearchChange(event: React.ChangeEvent<HTMLInputElement>) {
        if (!this.component) {
            return;
        }

        const searchTerm = event.target.value;
        this.component.setState({ search_query: searchTerm, current_page: 1 });
        this.debouncedUpdateFilteredData();
    }

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredBuildingData();
    }, 300);

    sendOrders() {
        if (!this.component) {
            return;
        }

        this.component.setState(
            {
                processing_request: true,
                success_message: null,
                error_message: null,
            },
            () => {
                if (!this.component) {
                    return;
                }

                this.processBuildingRequest.sendBuildingRequests(
                    this.component,
                    this.component.props.kingdom.character_id,
                    this.component.props.kingdom.id,
                    this.component.state.building_queue,
                );
            },
        );
    }

    toggleBuildingQueue(kingdomId: number, buildingId: number) {
        if (!this.component) {
            return;
        }

        if (!this.canToggleBuildingForQueue(kingdomId, buildingId)) {
            return;
        }

        this.component.setState((prevState: BuildingsToUpgradeSectionState) => {
            const queue = [...prevState.building_queue];

            const kingdomQueue = queue.find(
                (item: BuildingQueue) => item.kingdomId === kingdomId,
            );

            if (kingdomQueue) {
                const buildingIndex =
                    kingdomQueue.buildingIds.indexOf(buildingId);

                if (buildingIndex > -1) {
                    kingdomQueue.buildingIds.splice(buildingIndex, 1);
                    if (kingdomQueue.buildingIds.length === 0) {
                        queue.splice(queue.indexOf(kingdomQueue), 1);
                    }
                } else {
                    kingdomQueue.buildingIds.push(buildingId);
                }
            } else {
                queue.push({
                    kingdomId,
                    buildingIds: [buildingId],
                });
            }

            return { building_queue: queue };
        });
    }

    toggleQueueAllBuildingsForAllKingdoms() {
        if (!this.component) {
            return;
        }

        const queue = [...this.component.state.building_queue];

        if (queue.length > 0) {
            this.component.setState({
                building_queue: [],
            });

            return;
        }

        const allKingdoms = this.component.state.building_data || [];

        allKingdoms.forEach((kingdom: Kingdom) => {
            this.toggleQueueAllBuildings(kingdom.kingdom_id);
        });
    }

    toggleQueueAllBuildings(kingdomId: number) {
        if (!this.component) {
            return;
        }

        this.component.setState((prevState: BuildingsToUpgradeSectionState) => {
            if (!this.component) {
                return prevState;
            }

            const queue = [...prevState.building_queue];
            const kingdomQueue = queue.find(
                (item: BuildingQueue) => item.kingdomId === kingdomId,
            );

            const buildings =
                (
                    this.component.state.filtered_building_data.find(
                        (k: Kingdom) => k.kingdom_id === kingdomId,
                    ) || {}
                ).buildings || [];

            if (buildings.length <= 0) {
                return prevState;
            }

            const validBuildingIds = buildings
                .filter((b: Building) =>
                    this.canToggleBuildingForQueue(kingdomId, b.id),
                )
                .map((b: Building) => b.id);

            if (validBuildingIds.length <= 0) {
                return {
                    ...prevState,
                    error_message:
                        "You have no buildings to queue. Check out some of the kingdoms below to see why? Chances are you have not trained the appropriate passive skill. Expanding the kingdom to see the buildings will tell you why they cannot be queued.",
                };
            }

            if (kingdomQueue) {
                if (
                    kingdomQueue.buildingIds.length === validBuildingIds.length
                ) {
                    queue.splice(queue.indexOf(kingdomQueue), 1);
                } else {
                    kingdomQueue.buildingIds = validBuildingIds;
                }
            } else {
                queue.push({
                    kingdomId,
                    buildingIds: validBuildingIds,
                });
            }

            return { building_queue: queue, error_message: null };
        });
    }

    canToggleBuildingForQueue(kingdomId: number, buildingId: number): boolean {
        if (!this.component) {
            return false;
        }

        const foundKingdom = this.component.state.building_data.find(
            (buildingData: Kingdom) => buildingData.kingdom_id === kingdomId,
        );

        const foundBuilding = foundKingdom?.buildings.find(
            (building: Building) => building.id === buildingId,
        );

        if (
            !foundBuilding ||
            (foundBuilding.passive_required_for_building &&
                !foundBuilding.passive_required_for_building.is_trained)
        ) {
            return false;
        }

        return true;
    }

    handlePageChange(pageNumber: number) {
        if (!this.component) {
            return;
        }

        this.component.setState({ current_page: pageNumber });
    }

    getPaginatedData() {
        if (!this.component) {
            return [];
        }

        const { current_page, itemsPerPage, filtered_building_data } =
            this.component.state;
        const startIndex = (current_page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        return filtered_building_data.slice(startIndex, endIndex);
    }

    hasBuildingInQueue(kingdom: Kingdom, building: Building): boolean {
        if (!this.component) {
            return false;
        }

        return this.component.state.building_queue.some(
            (item: BuildingQueue) =>
                item.kingdomId === kingdom.kingdom_id &&
                item.buildingIds.includes(building.id),
        );
    }
}
