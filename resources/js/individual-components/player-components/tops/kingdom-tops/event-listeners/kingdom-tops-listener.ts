import { injectable } from "tsyringe";
import TopsListener from "../../shared/event-listeners/tops-listener";
import { topsServiceContainer } from "../../shared/container/tops-container";
import KingdomTops from "../kingdom-tops";
import KingdomTopsListenerDefinition from "./kingdom-tops-listener-definition";
import TopsEventPayload from "../../shared/types/tops-event-payload";

@injectable()
export default class KingdomTopsListener
    implements KingdomTopsListenerDefinition
{
    private listener: TopsListener;

    private component?: KingdomTops;

    constructor() {
        this.listener = topsServiceContainer().fetch(TopsListener);
    }

    initialize(component: KingdomTops): void {
        this.component = component;
    }

    register(): void {
        this.listener.initialize(
            "tops-kingdom-leaderboard",
            "Game.Tops.Events.KingdomTopsUpdated",
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
