import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import CapitalCityUnitRecruitmentEventDefinition from "./capital-city-unit-recruitment-event-definition";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";

@injectable()
export default class CapitalCityUnitRecruitmentEvent
    implements CapitalCityUnitRecruitmentEventDefinition
{
    private component?: UnitRecruitment;

    private userId?: number;

    private capitalCityUnitRecruitmentEvent?: Channel;

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

            this.capitalCityUnitRecruitmentEvent = echo.private(
                "capital-city-update-kingdom-unit-data-" + this.userId,
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
        if (!this.capitalCityUnitRecruitmentEvent) {
            return;
        }

        this.capitalCityUnitRecruitmentEvent.listen(
            "Game.Kingdoms.Events.UpdateCapitalCityUnitRecruitments",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let data = this.keepFadingKingdomsRendered(
                    this.component.state.unit_recruitment_data,
                    event.kingdomUnitRecruitment,
                );

                this.component.setState({
                    unit_recruitment_data: data,
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
                .filter((kingdom: any) => !fadingKingdomIds.has(kingdom.id))
                .map((kingdom: any) => [kingdom.id, kingdom]),
        );

        const mergedData = currentData
            .map((kingdom: any) => {
                if (fadingKingdomIds.has(kingdom.id)) {
                    return kingdom;
                }

                return incomingDataById.get(kingdom.id) ?? null;
            })
            .filter((kingdom: any) => kingdom !== null);

        incomingDataById.forEach((kingdom: any, kingdomId: number) => {
            if (!currentData.some((current: any) => current.id === kingdomId)) {
                mergedData.push(kingdom);
            }
        });

        return mergedData;
    }
}
