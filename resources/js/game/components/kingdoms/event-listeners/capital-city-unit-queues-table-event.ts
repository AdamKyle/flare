import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import UnitQueuesTable from "../capital-city/unit-queues-table";
import CapitalCityUnitQueueTableEventDefinition from "./capital-city-unit-queue-table-event-definition";

@injectable()
export default class CapitalCityUnitQueuesTableEvent
    implements CapitalCityUnitQueueTableEventDefinition
{
    private component?: UnitQueuesTable;

    private userId?: number;

    private capitalCityUnitUpgradeRepairTableEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: UnitQueuesTable, userId: number): void {
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
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenForUnitTableUpdate();
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

                let data = event.unitQueueData;

                this.component.setState({
                    unit_queues: data,
                });
            },
        );
    }
}
