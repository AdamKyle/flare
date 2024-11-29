import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import ActionsTimers from "../actions-timers";
import ActionTimerListener from "./action-timer-listener";

@injectable()
export default class ActionTimerListeners implements ActionTimerListener {
    private component?: ActionsTimers;
    private userId?: number;

    private attackTimeout?: Channel;
    private craftingTimeout?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: ActionsTimers, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.attackTimeout = echo.private(
                "show-timeout-bar-" + this.userId,
            );

            this.craftingTimeout = echo.private(
                "show-crafting-timeout-bar-" + this.userId,
            );

            console.log(this.attackTimeout, this.craftingTimeout);
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForAttackTimerUpdate();
        this.listenForCraftingTimerUpdate();
    }

    private listenForAttackTimerUpdate(): void {
        if (!this.attackTimeout) {
            return;
        }

        this.attackTimeout.listen(
            "Game.Core.Events.ShowTimeOutEvent",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    attack_time_out: event.forLength,
                });
            },
        );
    }

    private listenForCraftingTimerUpdate(): void {
        if (!this.craftingTimeout) {
            return;
        }

        this.craftingTimeout.listen(
            "Game.Core.Events.ShowCraftingTimeOutEvent",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    crafting_time_out: event.timeout,
                });
            },
        );
    }
}
