import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import FactionLoyaltyListener from "./faction-loyalty-listener";
import FactionFame from "../faction-fame";

@injectable()
export default class FactionLoyaltyListeners implements FactionLoyaltyListener {
    private component?: FactionFame;
    private userId?: number;

    private factionLoyaltyUpdate?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: FactionFame, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.factionLoyaltyUpdate = echo.private(
                "faction-loyalty-update-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForFactionLoyaltyUpdate();
    }

    private listenForFactionLoyaltyUpdate(): void {
        if (!this.factionLoyaltyUpdate) {
            return;
        }

        this.factionLoyaltyUpdate.listen(
            "Game.Factions.FactionLoyalty.Events.FactionLoyaltyUpdate",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState(
                    {
                        npcs: event.factionLoyalty.npcs,
                        game_map_name: event.factionLoyalty.map_name,
                        faction_loyalty: event.factionLoyalty.faction_loyalty,
                    },
                    () => {
                        if (!this.component) {
                            return;
                        }

                        this.component.setInitialSelectedFactionInfo(
                            event.factionLoyalty.faction_loyalty,
                            event.factionLoyalty.npcs,
                        );
                    },
                );
            },
        );
    }
}
