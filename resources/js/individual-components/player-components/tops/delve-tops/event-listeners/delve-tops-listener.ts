import { injectable } from "tsyringe";
import TopsListener from "../../shared/event-listeners/tops-listener";
import { topsServiceContainer } from "../../shared/container/tops-container";
import DelveTops from "../delve-tops";
import DelveTopsListenerDefinition from "./delve-tops-listener-definition";
import TopsEventPayload from "../../shared/types/tops-event-payload";

@injectable()
export default class DelveTopsListener implements DelveTopsListenerDefinition {
    private listener: TopsListener;

    private component?: DelveTops;

    constructor() {
        this.listener = topsServiceContainer().fetch(TopsListener);
    }

    initialize(component: DelveTops): void {
        this.component = component;
    }

    register(): void {
        this.listener.initialize(
            "tops-delve-leaderboard",
            "Game.Tops.Events.DelveTopsUpdated",
            (event: TopsEventPayload) => {
                if (this.component && event.leaderboard) {
                    this.component.fetchLeaderboard();
                }
            },
        );
        this.listener.register();
    }

    listen(): void {
        this.listener.listen();
    }
}
