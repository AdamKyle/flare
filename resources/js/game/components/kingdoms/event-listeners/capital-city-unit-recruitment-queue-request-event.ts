import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import CapitalCityUnitRecruitmentQueueRequestEventDefinition from "./capital-city-unit-recruitment-queue-request-event-definition";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";

@injectable()
export default class CapitalCityUnitRecruitmentQueueRequestEvent
    implements CapitalCityUnitRecruitmentQueueRequestEventDefinition
{
    private component?: UnitRecruitment;

    private userId?: number;

    private capitalCityUnitRecruitmentQueueRequestEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: UnitRecruitment, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.capitalCityUnitRecruitmentQueueRequestEvent = echo.private(
                "capital-city-unit-queue-request-" + this.userId,
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
        if (!this.capitalCityUnitRecruitmentQueueRequestEvent) {
            return;
        }

        this.capitalCityUnitRecruitmentQueueRequestEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityUnitQueueRequest",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                if (event.type === "progress") {
                    const processedKingdomId = event.processed_kingdom_id;
                    const queuedUnitNames = this.fetchQueuedUnitNames(event);

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

                    const shouldFadeKingdom = this.kingdomShouldFade(
                        this.component.state.unit_recruitment_data,
                        processedKingdomId,
                        queuedUnitNames,
                    );
                    const bulkInputValues = {
                        ...this.component.state.bulk_input_values,
                    };

                    delete bulkInputValues[processedKingdomId];

                    if (shouldFadeKingdom) {
                        this.fadeAndRemoveKingdom(
                            processedKingdomId,
                            queuedUnitNames,
                            bulkInputValues,
                        );

                        return;
                    }

                    const unitRecruitmentData =
                        this.removeQueuedUnitsFromKingdoms(
                            this.component.state.unit_recruitment_data,
                            processedKingdomId,
                            queuedUnitNames,
                        );
                    const filteredUnitRecruitmentData =
                        this.removeQueuedUnitsFromKingdoms(
                            this.component.state.filtered_unit_recruitment_data,
                            processedKingdomId,
                            queuedUnitNames,
                        );
                    const totalPages = this.calculateTotalPages(
                        filteredUnitRecruitmentData.length,
                    );

                    this.component.setState({
                        processing_request: false,
                        unit_recruitment_data: unitRecruitmentData,
                        filtered_unit_recruitment_data:
                            filteredUnitRecruitmentData,
                        unit_queue: this.removeQueuedUnitsFromUnitQueue(
                            this.component.state.unit_queue,
                            processedKingdomId,
                            queuedUnitNames,
                        ),
                        bulk_input_values: bulkInputValues,
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

    private fetchQueuedUnitNames(event: any): string[] {
        if (Array.isArray(event.queued_unit_names)) {
            return event.queued_unit_names;
        }

        if (Array.isArray(event.queue_data?.unit_requests)) {
            return event.queue_data.unit_requests.map(
                (unitRequest: any) => unitRequest.unit_name,
            );
        }

        return [];
    }

    private kingdomShouldFade(
        kingdoms: any[],
        processedKingdomId: number,
        queuedUnitNames: string[],
    ): boolean {
        const kingdom = kingdoms.find(
            (kingdom: any) => kingdom.id === processedKingdomId,
        );

        if (!kingdom) {
            return false;
        }

        return (
            kingdom.available_unit_types.filter(
                (unitName: string) => !queuedUnitNames.includes(unitName),
            ).length <= 0
        );
    }

    private fadeAndRemoveKingdom(
        processedKingdomId: number,
        queuedUnitNames: string[],
        bulkInputValues: any,
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
            unit_queue: this.removeQueuedUnitsFromUnitQueue(
                this.component.state.unit_queue,
                processedKingdomId,
                queuedUnitNames,
            ),
            bulk_input_values: bulkInputValues,
            fading_kingdom_ids: fadingKingdomIds,
        });

        window.setTimeout(() => {
            if (!this.component) {
                return;
            }

            const unitRecruitmentData =
                this.component.state.unit_recruitment_data.filter(
                    (kingdom: any) => kingdom.id !== processedKingdomId,
                );
            const filteredUnitRecruitmentData =
                this.component.state.filtered_unit_recruitment_data.filter(
                    (kingdom: any) => kingdom.id !== processedKingdomId,
                );
            const nextFadingKingdomIds = new Set(
                this.component.state.fading_kingdom_ids,
            );

            nextFadingKingdomIds.delete(processedKingdomId);

            this.component.setState({
                unit_recruitment_data: unitRecruitmentData,
                filtered_unit_recruitment_data: filteredUnitRecruitmentData,
                fading_kingdom_ids: nextFadingKingdomIds,
                current_page: Math.min(
                    this.component.state.current_page,
                    this.calculateTotalPages(
                        filteredUnitRecruitmentData.length,
                    ),
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
            Math.ceil(totalItems / this.component.state.items_per_page),
        );
    }

    private removeQueuedUnitsFromKingdoms(
        kingdoms: any[],
        processedKingdomId: number,
        queuedUnitNames: string[],
    ): any[] {
        return kingdoms
            .map((kingdom: any) => {
                if (kingdom.id !== processedKingdomId) {
                    return kingdom;
                }

                const availableUnitTypes = kingdom.available_unit_types.filter(
                    (unitName: string) => !queuedUnitNames.includes(unitName),
                );

                if (availableUnitTypes.length <= 0) {
                    return null;
                }

                return {
                    ...kingdom,
                    available_unit_types: availableUnitTypes,
                };
            })
            .filter((kingdom: any) => kingdom !== null);
    }

    private removeQueuedUnitsFromUnitQueue(
        unitQueue: any[],
        processedKingdomId: number,
        queuedUnitNames: string[],
    ): any[] {
        return unitQueue
            .map((kingdomQueue: any) => {
                if (kingdomQueue.kingdom_id !== processedKingdomId) {
                    return kingdomQueue;
                }

                const unitRequests = kingdomQueue.unit_requests.filter(
                    (unitRequest: any) =>
                        !queuedUnitNames.includes(unitRequest.unit_name),
                );

                if (unitRequests.length <= 0) {
                    return null;
                }

                return {
                    ...kingdomQueue,
                    unit_requests: unitRequests,
                };
            })
            .filter((kingdomQueue: any) => kingdomQueue !== null);
    }
}
