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
                "capital-city-update-kingdom-building-data-" + this.userId,
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
            "Game.Kingdoms.Events.UpdateCapitalCityBuildingUpgrades",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let data = event.kingdomBuildingData;

                // Filter based on repair status
                if (this.component.props.repair) {
                    data = data
                        .map(
                            (kingdom: {
                                map_name: any;
                                kingdom_name: any;
                                kingdom_id: number;
                                buildings: any[];
                            }) => ({
                                kingdom_name: kingdom.kingdom_name,
                                kingdom_id: kingdom.kingdom_id,
                                map_name: kingdom.map_name,
                                buildings: kingdom.buildings
                                    .filter(
                                        (building) =>
                                            building.current_durability <
                                            building.max_durability,
                                    )
                                    .sort((a, b) => a.level - b.level),
                            }),
                        )
                        .filter((kingdom: any) => kingdom.buildings.length > 0);
                } else {
                    data = data.map(
                        (kingdom: {
                            map_name: any;
                            kingdom_name: any;
                            kingdom_id: number;
                            buildings: any[];
                        }) => ({
                            kingdom_name: kingdom.kingdom_name,
                            map_name: kingdom.map_name,
                            kingdom_id: kingdom.kingdom_id,
                            buildings: kingdom.buildings
                                .filter(
                                    (building) =>
                                        building.current_durability >=
                                        building.max_durability,
                                )
                                .sort((a, b) => a.level - b.level),
                        }),
                    );
                }

                this.component.setState({
                    loading: false,
                    building_data: data,
                });
            },
        );
    }
}
