import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "./capital-city-building-upgrade-repair-table-event-definition";
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
                    const itemsPerPage = this.component.state.itemsPerPage;
                    const filteredBuildingData =
                        this.component.state.filtered_building_data.filter(
                            (kingdom: any) =>
                                kingdom.kingdom_id !== processedKingdomId,
                        );
                    const totalPages = Math.max(
                        1,
                        Math.ceil(filteredBuildingData.length / itemsPerPage),
                    );

                    this.component.setState({
                        processing_request: false,
                        building_data:
                            this.component.state.building_data.filter(
                                (kingdom: any) =>
                                    kingdom.kingdom_id !== processedKingdomId,
                            ),
                        filtered_building_data: filteredBuildingData,
                        building_queue:
                            this.component.state.building_queue.filter(
                                (kingdom: any) =>
                                    kingdom.kingdomId !== processedKingdomId,
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
}
