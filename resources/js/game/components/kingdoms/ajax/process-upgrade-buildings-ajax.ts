import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
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
                    this.applyAcceptedRequestState(component, params);
                    component.setState({
                        processing_request: false,
                        info_message: "Building orders were accepted.",
                        success_message: null,
                        error_message: null,
                    });
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

    private applyAcceptedRequestState(
        component: BuildingsToUpgradeSection,
        submittedQueue: any[],
    ): void {
        const queuedBuildingIdsByKingdom =
            this.queuedBuildingIdsByKingdom(submittedQueue);
        const updatedBuildingData = this.removeQueuedBuildingsFromKingdoms(
            component.state.building_data,
            queuedBuildingIdsByKingdom,
        );
        const updatedFilteredBuildingData =
            this.removeQueuedBuildingsFromKingdoms(
                component.state.filtered_building_data,
                queuedBuildingIdsByKingdom,
            );
        const fadingKingdomIds = this.fadingKingdomIds(
            component.state.building_data,
            updatedBuildingData,
            queuedBuildingIdsByKingdom,
        );

        if (fadingKingdomIds.size <= 0) {
            component.setState({
                building_data: updatedBuildingData,
                filtered_building_data: updatedFilteredBuildingData,
                building_queue: this.removeQueuedBuildingsFromBuildingQueue(
                    component.state.building_queue,
                    queuedBuildingIdsByKingdom,
                ),
                current_page: Math.min(
                    component.state.current_page,
                    this.calculateTotalPages(
                        component,
                        updatedFilteredBuildingData.length,
                    ),
                ),
            });

            return;
        }

        const nextFadingKingdomIds = new Set(
            component.state.fading_kingdom_ids,
        );

        fadingKingdomIds.forEach((kingdomId: number) => {
            nextFadingKingdomIds.add(kingdomId);
        });

        component.setState({
            building_data: this.keepFadingKingdomsRendered(
                component.state.building_data,
                updatedBuildingData,
                fadingKingdomIds,
            ),
            filtered_building_data: this.keepFadingKingdomsRendered(
                component.state.filtered_building_data,
                updatedFilteredBuildingData,
                fadingKingdomIds,
            ),
            building_queue: this.removeQueuedBuildingsFromBuildingQueue(
                component.state.building_queue,
                queuedBuildingIdsByKingdom,
                fadingKingdomIds,
            ),
            fading_kingdom_ids: nextFadingKingdomIds,
        });

        window.setTimeout(() => {
            const currentFadingKingdomIds = new Set(
                component.state.fading_kingdom_ids,
            );

            fadingKingdomIds.forEach((kingdomId: number) => {
                currentFadingKingdomIds.delete(kingdomId);
            });

            component.setState({
                building_data: updatedBuildingData,
                filtered_building_data: updatedFilteredBuildingData,
                building_queue: this.removeQueuedBuildingsFromBuildingQueue(
                    component.state.building_queue,
                    queuedBuildingIdsByKingdom,
                ),
                fading_kingdom_ids: currentFadingKingdomIds,
                current_page: Math.min(
                    component.state.current_page,
                    this.calculateTotalPages(
                        component,
                        updatedFilteredBuildingData.length,
                    ),
                ),
            });
        }, 300);
    }

    private queuedBuildingIdsByKingdom(submittedQueue: any[]): any {
        const queuedBuildingIdsByKingdom: any = {};

        submittedQueue.forEach((kingdomQueue: any) => {
            queuedBuildingIdsByKingdom[kingdomQueue.kingdomId] = (
                kingdomQueue.buildingIds ?? []
            ).map((buildingId: number) => Number(buildingId));
        });

        return queuedBuildingIdsByKingdom;
    }

    private removeQueuedBuildingsFromKingdoms(
        kingdoms: any[],
        queuedBuildingIdsByKingdom: any,
    ): any[] {
        return kingdoms
            .map((kingdom: any) => {
                const queuedBuildingIds =
                    queuedBuildingIdsByKingdom[kingdom.kingdom_id];

                if (!Array.isArray(queuedBuildingIds)) {
                    return kingdom;
                }

                const buildings = kingdom.buildings.filter(
                    (building: any) => !queuedBuildingIds.includes(building.id),
                );

                if (buildings.length <= 0) {
                    return null;
                }

                return {
                    ...kingdom,
                    buildings,
                };
            })
            .filter((kingdom: any) => kingdom !== null);
    }

    private fadingKingdomIds(
        originalKingdoms: any[],
        updatedKingdoms: any[],
        queuedBuildingIdsByKingdom: any,
    ): Set<number> {
        const updatedKingdomIds = new Set(
            updatedKingdoms.map((kingdom: any) => kingdom.kingdom_id),
        );
        const fadingKingdomIds = new Set<number>();

        originalKingdoms.forEach((kingdom: any) => {
            if (
                Array.isArray(queuedBuildingIdsByKingdom[kingdom.kingdom_id]) &&
                !updatedKingdomIds.has(kingdom.kingdom_id)
            ) {
                fadingKingdomIds.add(kingdom.kingdom_id);
            }
        });

        return fadingKingdomIds;
    }

    private keepFadingKingdomsRendered(
        originalKingdoms: any[],
        updatedKingdoms: any[],
        fadingKingdomIds: Set<number>,
    ): any[] {
        const updatedKingdomsById = new Map(
            updatedKingdoms.map((kingdom: any) => [
                kingdom.kingdom_id,
                kingdom,
            ]),
        );

        return originalKingdoms
            .map((kingdom: any) => {
                if (fadingKingdomIds.has(kingdom.kingdom_id)) {
                    return kingdom;
                }

                return updatedKingdomsById.get(kingdom.kingdom_id) ?? null;
            })
            .filter((kingdom: any) => kingdom !== null);
    }

    private removeQueuedBuildingsFromBuildingQueue(
        buildingQueue: any[],
        queuedBuildingIdsByKingdom: any,
        fadingKingdomIds: Set<number> = new Set(),
    ): any[] {
        return buildingQueue
            .map((kingdomQueue: any) => {
                if (fadingKingdomIds.has(kingdomQueue.kingdomId)) {
                    return kingdomQueue;
                }

                const queuedBuildingIds =
                    queuedBuildingIdsByKingdom[kingdomQueue.kingdomId];

                if (!Array.isArray(queuedBuildingIds)) {
                    return kingdomQueue;
                }

                const buildingIds = kingdomQueue.buildingIds.filter(
                    (buildingId: number) =>
                        !queuedBuildingIds.includes(buildingId),
                );

                if (buildingIds.length <= 0) {
                    return null;
                }

                return {
                    ...kingdomQueue,
                    buildingIds,
                };
            })
            .filter((kingdomQueue: any) => kingdomQueue !== null);
    }

    private calculateTotalPages(
        component: BuildingsToUpgradeSection,
        totalItems: number,
    ): number {
        return Math.max(
            1,
            Math.ceil(totalItems / component.state.itemsPerPage),
        );
    }
}
