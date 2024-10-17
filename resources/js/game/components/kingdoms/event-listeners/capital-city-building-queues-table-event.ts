import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import BuildingsInQueue from "../capital-city/buildings-in-queue";
import CapitalCityBuildingQueueTableEventDefinition from "./capital-city-building-queue-table-event-definition";

@injectable()
export default class CapitalCityBuildingQueuesTableEvent
    implements CapitalCityBuildingQueueTableEventDefinition {
    private component?: BuildingsInQueue;

    private userId?: number;

    private capitalCityBuildingUpgradeRepairTableEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) { }

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
        if (!this.capitalCityBuildingUpgradeRepairTableEvent) {
            return;
        }

        this.capitalCityBuildingUpgradeRepairTableEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityBuildingQueueTable",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let data = event.buildingQueueData;

                this.component.setState({
                    building_queues: data,
                });
            },
        );
    }
}
