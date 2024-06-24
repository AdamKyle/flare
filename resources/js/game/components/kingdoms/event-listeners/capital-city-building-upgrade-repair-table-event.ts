import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CapitalCityBuildingUpgradeRepairTableEventDefinition from "./capital-city-building-upgrade-repair-table-event-definition";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";

@injectable()
export default class CapitalCityBuildingUpgradeRepairTableEvent
    implements CapitalCityBuildingUpgradeRepairTableEventDefinition
{
    private component?: BuildingsToUpgradeSection;

    private userId?: number;

    private capitalCityBuildingUpgradeRepairTableEvent?: Channel;

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

            this.capitalCityBuildingUpgradeRepairTableEvent = echo.private(
                "update-kingdom-building-data-" + this.userId,
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
            "Game.Kingdoms.Events.UpdateBuildingUpgrades",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let data = event.kingdomBuildingData;

                if (this.component.props.repair) {
                    data = data.map(
                        (kingdom: {
                            kingdom_name: any;
                            kingdom_id: number;
                            buildings: any[];
                        }) => ({
                            kingdom_name: kingdom.kingdom_name,
                            kingdom_id: kingdom.kingdom_id,
                            buildings: kingdom.buildings.filter(
                                (building) =>
                                    building.current_durability <
                                    building.max_durability,
                            ),
                        }),
                    );
                }

                this.component.compressArray(data, false);

                this.component.setState({
                    loading: false,
                    building_data: data,
                });
            },
        );
    }
}
