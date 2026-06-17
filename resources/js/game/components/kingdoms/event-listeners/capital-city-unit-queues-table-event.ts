import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import CapitalCityUnitQueueTableEventDefinition from "./capital-city-unit-queue-table-event-definition";
import UnitQueue from "../capital-city/partials/unit-management/unit-queue";

@injectable()
export default class CapitalCityUnitQueuesTableEvent
    implements CapitalCityUnitQueueTableEventDefinition
{
    private component?: UnitQueue;

    private userId?: number;

    private capitalCityUnitUpgradeRepairTableEvent?: Channel;

    private capitalCityUnitRecruitmentQueueRequestEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: UnitQueue, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.capitalCityUnitUpgradeRepairTableEvent = echo.private(
                "capital-city-unit-queue-data-" + this.userId,
            );

            this.capitalCityUnitRecruitmentQueueRequestEvent = echo.private(
                "capital-city-unit-queue-request-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenForUnitTableUpdate();
        this.listenForUnitProgressUpdate();
    }

    /**
     * Listen for the table to update.
     *
     * @protected
     */
    protected listenForUnitTableUpdate() {
        if (!this.capitalCityUnitUpgradeRepairTableEvent) {
            return;
        }

        this.capitalCityUnitUpgradeRepairTableEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityUnitQueueTable",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    unit_queues: event.unitQueueData,
                });
            },
        );
    }

    protected listenForUnitProgressUpdate() {
        if (!this.capitalCityUnitRecruitmentQueueRequestEvent) {
            return;
        }

        this.capitalCityUnitRecruitmentQueueRequestEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityUnitQueueRequest",
            (event: any) => {
                if (!this.component || event.type !== "progress") {
                    return;
                }

                if (!event.queue_data) {
                    return;
                }

                const queueData = event.queue_data;

                this.component.setState((prevState: any) => ({
                    unit_queues: [
                        ...prevState.unit_queues.filter(
                            (queue: any) =>
                                queue.kingdom_id !== queueData.kingdom_id,
                        ),
                        queueData,
                    ],
                }));
            },
        );
    }
}
