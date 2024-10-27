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

                let data = event.kingdomUnitRecruitment;

                this.component.setState({
                    unit_recruitment_data: data,
                });
            },
        );
    }
}
