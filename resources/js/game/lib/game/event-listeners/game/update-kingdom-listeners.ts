import {inject, injectable} from "tsyringe";
import CoreEventListener from "../core-event-listener";
import {Channel} from "laravel-echo";
import KingdomDetails from "../../kingdoms/kingdom-details";
import Kingdom from "../../../../sections/kingdoms/kingdom";
import KingdomEventListener from "../kingdom-event-listener";

@injectable()
export default class UpdateKingdomListeners implements KingdomEventListener {

    private component?: Kingdom;

    private userId?: number;

    private kingdomUpdates?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    public initialize(component: Kingdom, userId: number): void {
        this.component = component;
        this.userId    = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.kingdomUpdates = echo.private("update-kingdom-" + this.userId);

        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenToKingdomUpdates();
    }

    /**
     * Listen to when the kingdom updates
     *
     * @protected
     */
    protected listenToKingdomUpdates() {
        if (!this.kingdomUpdates) {
            return
        }

        this.kingdomUpdates.listen(
            "Game.Kingdoms.Events.UpdateKingdom",
            (event: { kingdom: KingdomDetails }) => {

                if (!this.component) {
                    return;
                }

                this.component.setState({
                    kingdom: event.kingdom,
                });
            }
        );
    }
}
