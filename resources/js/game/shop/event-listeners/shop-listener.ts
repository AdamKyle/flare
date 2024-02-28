
import {inject, injectable} from "tsyringe";
import {Channel} from "laravel-echo";
import Shop from "../shop";
import CoreEventListener from "../../lib/game/event-listeners/core-event-listener";
import ShopListenerDefinition from "./shop-listener-definition";

@injectable()
export default class ShopListener implements ShopListenerDefinition {

    private component?: Shop;
    private userId?: number;

    private shop?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    initialize(component: Shop, userId: number): void {
        this.component = component;
        this.userId    = userId
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.shop = echo.private("update-shop-" + this.userId);
        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForShopUpdates();
    }

    /**
     * Listen to the character top bar event.
     *
     * @protected
     */
    protected listenForShopUpdates() {
        if (!this.shop) {
            return;
        }

        this.shop.listen(
            "Game.Shop.Events.UpdateShopEvent",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                this.component.setState({
                    gold: event.gold,
                    inventory_count: event.inventoryCount,
                });
            }
        );
    }
}
