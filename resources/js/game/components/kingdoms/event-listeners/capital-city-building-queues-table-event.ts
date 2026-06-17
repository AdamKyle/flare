import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import BuildingsInQueue from "../capital-city/buildings-in-queue";
import CapitalCityBuildingQueueTableEventDefinition from "./capital-city-building-queue-table-event-definition";

@injectable()
export default class CapitalCityBuildingQueuesTableEvent
    implements CapitalCityBuildingQueueTableEventDefinition
{
    private component?: BuildingsInQueue;

    private userId?: number;

    private capitalCityBuildingUpgradeRepairTableEvent?: Channel;

    private capitalCityBuildingQueueRequestEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: BuildingsInQueue, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.capitalCityBuildingUpgradeRepairTableEvent = echo.private(
                "capital-city-building-queue-data-" + this.userId,
            );

            this.capitalCityBuildingQueueRequestEvent = echo.private(
                "capital-city-building-queue-request-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenForTableUpdate();
        this.listenForProgressUpdate();
    }

    /**
     * Listen for the table to update.
     *
     * @protected
     */
    protected listenForTableUpdate() {
        if (!this.capitalCityBuildingUpgradeRepairTableEvent) {
            return;
        }

        this.capitalCityBuildingUpgradeRepairTableEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityBuildingQueueTable",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                const timerStartedAt = Date.now();
                let data = event.buildingQueueData.map((queue: any) => ({
                    ...queue,
                    timer_started_at: timerStartedAt,
                }));

                this.component.setState({
                    building_queues: data,
                });
            },
        );
    }

    protected listenForProgressUpdate() {
        if (!this.capitalCityBuildingQueueRequestEvent) {
            return;
        }

        this.capitalCityBuildingQueueRequestEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityBuildingQueueRequest",
            (event: any) => {
                if (!this.component || event.type !== "progress") {
                    return;
                }

                if (!event.queue_data) {
                    return;
                }

                const queueData = {
                    ...event.queue_data,
                    timer_started_at: Date.now(),
                };

                this.component.setState((prevState: any) => ({
                    building_queues: [
                        ...prevState.building_queues.filter(
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
