import { injectable } from "tsyringe";
import TopsListener from "../../shared/event-listeners/tops-listener";
import { topsServiceContainer } from "../../shared/container/tops-container";
import FactionLoyaltyTops from "../faction-loyalty-tops";
import FactionLoyaltyTopsListenerDefinition from "./faction-loyalty-tops-listener-definition";
import TopsEventPayload from "../../shared/types/tops-event-payload";

@injectable()
export default class FactionLoyaltyTopsListener
    implements FactionLoyaltyTopsListenerDefinition
{
    private listener: TopsListener;

    private component?: FactionLoyaltyTops;

    constructor() {
        this.listener = topsServiceContainer().fetch(TopsListener);
    }

    initialize(component: FactionLoyaltyTops): void {
        this.component = component;
    }

    register(): void {
        this.listener.initialize(
            "tops-faction-loyalty-leaderboard",
            "Game.Tops.Events.FactionLoyaltyTopsUpdated",
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
