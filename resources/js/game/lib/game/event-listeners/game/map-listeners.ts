import GameListener from "../game-listener";
import Game from "../../../../game";
import {inject, injectable} from "tsyringe";
import CoreEventListener from "../core-event-listener";
import {Channel} from "laravel-echo";

@injectable()
export default class MapListeners implements GameListener {

    private component?: Game;

    private userId?: number;

    private traverseUpdate?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    public initialize(component: Game, userId: number): void {
        this.component = component;
        this.userId    = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.traverseUpdate = echo.private("update-plane-" + this.userId);
        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        if (this.traverseUpdate) {
            this.traverseUpdate.listen(
                "Game.Maps.Events.UpdateMap",
                (event: any) => {
                    if (!this.component) {
                        return;
                    }

                    this.component.setStateFromData(event.mapDetails);

                    this.component.updateQuestPlane(
                        event.mapDetails.character_map.game_map.name
                    );
                }
            );
        }
    }
}
