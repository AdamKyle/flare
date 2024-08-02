import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import KingdomPassiveSkillsEventDefinition from "./kingdom-passive-skills-event-definition";
import KingdomPassives from "../kingdom-passives";
import CoreEventListener from "../../../../lib/game/event-listeners/core-event-listener";

@injectable()
export default class KingdomPassiveSkillsEvent
    implements KingdomPassiveSkillsEventDefinition
{
    private component?: KingdomPassives;

    private userId?: number;

    private kingdomPassiveSkillUpdate?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: KingdomPassives, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.kingdomPassiveSkillUpdate = echo.private(
                "update-passive-skills-" + this.userId,
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
        if (!this.kingdomPassiveSkillUpdate) {
            return;
        }

        this.kingdomPassiveSkillUpdate.listen(
            "Game.PassiveSkills.Events.UpdatePassiveTree",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let data = event.passiveSkills;

                this.component.setState({
                    kingdom_passives: data,
                });
            },
        );
    }
}
