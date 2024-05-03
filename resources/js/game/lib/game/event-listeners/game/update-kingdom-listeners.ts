import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import Kingdom from "../../../../components/kingdoms/kingdom";
import SmallKingdom from "../../../../components/kingdoms/small-kingdom";
import CoreEventListener from "../core-event-listener";
import KingdomEventListener from "../kingdom-event-listener";
import KingdomDetails from "../../../../components/kingdoms/deffinitions/kingdom-details";

@injectable()
export default class UpdateKingdomListeners implements KingdomEventListener {
    private component?: Kingdom | SmallKingdom;

    private userId?: number;

    private kingdomUpdates?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: Kingdom | SmallKingdom, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.kingdomUpdates = echo.private("update-kingdom-" + this.userId);
        } catch (e: any | unknown) {
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
            return;
        }

        this.kingdomUpdates.listen(
            "Game.Kingdoms.Events.UpdateKingdom",
            (event: { kingdom: KingdomDetails }) => {
                if (!this.component) {
                    return;
                }

                if (this.component instanceof Kingdom) {

                    if (this.component.state.kingdom === null) {
                        return;
                    }

                    if (event.kingdom.id === this.component.state.kingdom.id) {
                        this.component.setState({
                            kingdom: event.kingdom,
                        });
                    }
                }

                if (this.component instanceof SmallKingdom) {

                    if (this.component.state.kingdom === null) {
                        return;
                    }

                    if (event.kingdom.id === this.component.state.kingdom.id) {
                        this.component.setState({
                            kingdom: event.kingdom,
                        });
                    }
                }
            },
        );
    }
}
