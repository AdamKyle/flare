import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "./capital-city-building-upgrade-repair-table-event-definition";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import CapitalCityBuildingQueueRequestEventDefinition from "./capital-city-building-queue-request-event-definition";
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
                    const itemsPerPage = this.component.state.items_per_page;
                    const filteredUnitRecruitmentData =
                        this.component.state.filtered_unit_recruitment_data.filter(
                            (kingdom: any) => kingdom.id !== processedKingdomId,
                        );
                    const totalPages = Math.max(
                        1,
                        Math.ceil(
                            filteredUnitRecruitmentData.length / itemsPerPage,
                        ),
                    );
                    const bulkInputValues = {
                        ...this.component.state.bulk_input_values,
                    };

                    delete bulkInputValues[processedKingdomId];

                    this.component.setState({
                        processing_request: false,
                        unit_recruitment_data:
                            this.component.state.unit_recruitment_data.filter(
                                (kingdom: any) =>
                                    kingdom.id !== processedKingdomId,
                            ),
                        filtered_unit_recruitment_data:
                            filteredUnitRecruitmentData,
                        unit_queue: this.component.state.unit_queue.filter(
                            (kingdom: any) =>
                                kingdom.kingdom_id !== processedKingdomId,
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
}
