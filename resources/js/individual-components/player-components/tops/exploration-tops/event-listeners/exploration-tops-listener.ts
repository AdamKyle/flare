import { injectable } from "tsyringe";
import TopsListener from "../../shared/event-listeners/tops-listener";
import { topsServiceContainer } from "../../shared/container/tops-container";
import ExplorationTops from "../exploration-tops";
import ExplorationTopsListenerDefinition from "./exploration-tops-listener-definition";
import TopsEventPayload from "../../shared/types/tops-event-payload";

@injectable()
export default class ExplorationTopsListener
    implements ExplorationTopsListenerDefinition
{
    private listener: TopsListener;

    private component?: ExplorationTops;

    constructor() {
        this.listener = topsServiceContainer().fetch(TopsListener);
    }

    initialize(component: ExplorationTops): void {
        this.component = component;
    }

    register(): void {
        this.listener.initialize(
            "tops-exploration-leaderboard",
            "Game.Tops.Events.ExplorationTopsUpdated",
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
