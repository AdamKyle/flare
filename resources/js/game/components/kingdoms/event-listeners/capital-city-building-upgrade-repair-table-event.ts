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
                                        (building) => building.can_be_repaired,
                                    )
                                    .sort((a, b) => a.level - b.level),
                            }),
                        )
                        .filter((kingdom: any) => kingdom.buildings.length > 0);
                } else {
                    data = data
                        .map(
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
                                        (building) => building.can_be_upgraded,
                                    )
                                    .sort((a, b) => a.level - b.level),
                            }),
                        )
                        .filter((kingdom: any) => kingdom.buildings.length > 0);
                }

                data = this.keepFadingKingdomsRendered(
                    this.component.state.building_data,
                    data,
                );

                this.component.setState({
                    loading: false,
                    building_data: data,
                });
            },
        );
    }

    private keepFadingKingdomsRendered(
        currentData: any[],
        incomingData: any[],
    ): any[] {
        if (!this.component) {
            return incomingData;
        }

        const fadingKingdomIds = this.component.state.fading_kingdom_ids;

        if (fadingKingdomIds.size <= 0) {
            return incomingData;
        }

        const incomingDataById = new Map(
            incomingData
                .filter(
                    (kingdom: any) => !fadingKingdomIds.has(kingdom.kingdom_id),
                )
                .map((kingdom: any) => [kingdom.kingdom_id, kingdom]),
        );

        const mergedData = currentData
            .map((kingdom: any) => {
                if (fadingKingdomIds.has(kingdom.kingdom_id)) {
                    return kingdom;
                }

                return incomingDataById.get(kingdom.kingdom_id) ?? null;
            })
            .filter((kingdom: any) => kingdom !== null);

        incomingDataById.forEach((kingdom: any, kingdomId: number) => {
            if (
                !currentData.some(
                    (current: any) => current.kingdom_id === kingdomId,
                )
            ) {
                mergedData.push(kingdom);
            }
        });

        return mergedData;
    }
}
