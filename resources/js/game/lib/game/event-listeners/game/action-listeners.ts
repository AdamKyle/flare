import GameListener from "../game-listener";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../core-event-listener";
import Game from "../../../../game";
import { Channel } from "laravel-echo";

@injectable()
export default class ActionListeners implements GameListener {
    private component?: Game;
    private userId?: number;

    private unlockAlchemySkill?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: Game, userId?: number): void {
        this.component = component;
        this.userId = userId;
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.unlockAlchemySkill = echo.private(
                "unlock-skill-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenToAlchemySkill();
    }

    /**
     * Listens for when we should unlock alchemy for the character.
     *
     * @protected
     */
    protected listenToAlchemySkill() {
        if (!this.unlockAlchemySkill) {
            return;
        }

        this.unlockAlchemySkill.listen(
            "Game.Quests.Events.UnlockSkillEvent",
            () => {
                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character),
                );

                character.is_alchemy_locked = false;

                this.component.setState({
                    character: character,
                });
            },
        );
    }
}
