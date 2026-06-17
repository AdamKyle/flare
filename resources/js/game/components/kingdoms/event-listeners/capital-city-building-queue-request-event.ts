import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import CapitalCityBuildingQueueRequestEventDefinition from "./capital-city-building-queue-request-event-definition";

@injectable()
export default class CapitalCityBuildingQueueRequestEvent
    implements CapitalCityBuildingQueueRequestEventDefinition
{
    private component?: BuildingsToUpgradeSection;

    private userId?: number;

    private capitalCityBuildingQueueRequestEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(
        component: BuildingsToUpgradeSection,
        userId: number,
    ): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.capitalCityBuildingQueueRequestEvent = echo.private(
                "capital-city-building-queue-request-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenForTableUpdate();
    }

    /**
     * Listen for the table to update.
     *
     * @protected
     */
    protected listenForTableUpdate() {
        if (!this.capitalCityBuildingQueueRequestEvent) {
            return;
        }

        this.capitalCityBuildingQueueRequestEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityBuildingQueueRequest",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                if (event.type === "progress") {
                    const processedKingdomId = event.processed_kingdom_id;
                    const queuedBuildingIds =
                        this.fetchQueuedBuildingIds(event);

                    if (
                        this.component.state.fading_kingdom_ids.has(
                            processedKingdomId,
                        )
                    ) {
                        this.component.setState({
                            processing_request: false,
                        });

                        return;
                    }

                    const buildingData = this.removeQueuedBuildingsFromKingdoms(
                        this.component.state.building_data,
                        processedKingdomId,
                        queuedBuildingIds,
                    );
                    const filteredBuildingData =
                        this.removeQueuedBuildingsFromKingdoms(
                            this.component.state.filtered_building_data,
                            processedKingdomId,
                            queuedBuildingIds,
                        );
                    const shouldFadeKingdom = this.kingdomShouldFade(
                        buildingData,
                        processedKingdomId,
                    );

                    if (shouldFadeKingdom) {
                        this.fadeAndRemoveKingdom(
                            processedKingdomId,
                            queuedBuildingIds,
                            buildingData,
                            filteredBuildingData,
                        );

                        return;
                    }

                    const totalPages = this.calculateTotalPages(
                        filteredBuildingData.length,
                    );

                    this.component.setState({
                        processing_request: false,
                        building_data: buildingData,
                        filtered_building_data: filteredBuildingData,
                        building_queue:
                            this.removeQueuedBuildingsFromBuildingQueue(
                                this.component.state.building_queue,
                                processedKingdomId,
                                queuedBuildingIds,
                            ),
                        current_page: Math.min(
                            this.component.state.current_page,
                            totalPages,
                        ),
                    });

                    return;
                }

                const nextState: any = {
                    processing_request: event.isLoading === true,
                };

                if (event.type === "success") {
                    nextState.success_message = event.message;
                    nextState.info_message = null;
                    nextState.error_message = null;
                }

                if (event.type === "info") {
                    nextState.info_message = event.message;
                    nextState.success_message = null;
                    nextState.error_message = null;
                }

                if (event.type === "error") {
                    nextState.error_message = event.message;
                    nextState.success_message = null;
                    nextState.info_message = null;
                }

                this.component.setState(nextState);
            },
        );
    }

    private fetchQueuedBuildingIds(event: any): number[] {
        if (Array.isArray(event.queued_building_ids)) {
            return event.queued_building_ids.map((buildingId: number) =>
                Number(buildingId),
            );
        }

        if (Array.isArray(event.queue_data?.building_queue)) {
            return event.queue_data.building_queue.map((building: any) =>
                Number(building.building_id),
            );
        }

        return [];
    }

    private kingdomShouldFade(
        kingdoms: any[],
        processedKingdomId: number,
    ): boolean {
        return !kingdoms.some(
            (kingdom: any) => kingdom.kingdom_id === processedKingdomId,
        );
    }

    private fadeAndRemoveKingdom(
        processedKingdomId: number,
        queuedBuildingIds: number[],
        buildingData: any[],
        filteredBuildingData: any[],
    ): void {
        if (!this.component) {
            return;
        }

        const fadingKingdomIds = new Set(
            this.component.state.fading_kingdom_ids,
        );

        fadingKingdomIds.add(processedKingdomId);

        this.component.setState({
            processing_request: false,
            fading_kingdom_ids: fadingKingdomIds,
        });

        window.setTimeout(() => {
            if (!this.component) {
                return;
            }

            const nextFadingKingdomIds = new Set(
                this.component.state.fading_kingdom_ids,
            );

            nextFadingKingdomIds.delete(processedKingdomId);

            this.component.setState({
                building_data: buildingData,
                filtered_building_data: filteredBuildingData,
                building_queue: this.removeQueuedBuildingsFromBuildingQueue(
                    this.component.state.building_queue,
                    processedKingdomId,
                    queuedBuildingIds,
                ),
                fading_kingdom_ids: nextFadingKingdomIds,
                current_page: Math.min(
                    this.component.state.current_page,
                    this.calculateTotalPages(filteredBuildingData.length),
                ),
            });
        }, 300);
    }

    private calculateTotalPages(totalItems: number): number {
        if (!this.component) {
            return 1;
        }

        return Math.max(
            1,
            Math.ceil(totalItems / this.component.state.itemsPerPage),
        );
    }

    private removeQueuedBuildingsFromKingdoms(
        kingdoms: any[],
        processedKingdomId: number,
        queuedBuildingIds: number[],
    ): any[] {
        return kingdoms
            .map((kingdom: any) => {
                if (kingdom.kingdom_id !== processedKingdomId) {
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

    private removeQueuedBuildingsFromBuildingQueue(
        buildingQueue: any[],
        processedKingdomId: number,
        queuedBuildingIds: number[],
    ): any[] {
        return buildingQueue
            .map((kingdomQueue: any) => {
                if (kingdomQueue.kingdomId !== processedKingdomId) {
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
}
