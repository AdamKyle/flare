import CoreEventListener from "./core-event-listener";
import {singleton, inject} from "tsyringe";
import {Channel} from "laravel-echo";
import Game from "../../../game";
import {serviceContainer} from "../../containers/core-container";
import GameListener from "./game-listener";
import MapListeners from "./game/map-listeners";

@singleton()
export default class GameEventListeners {

    private component?: Game;

    private userId?: number;

    private traverseUpdate?: GameListener;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    public initialize(component: Game, userId: number): void {
        this.component = component;
        this.userId    = userId;

        this.traverseUpdate = serviceContainer().fetch<GameListener>(MapListeners);
    }

    public registerEvents(): void {

        if (!this.component || !this.userId) {
            throw new Error('Need to call initialize on GameEventListeners first.');
        }


        if (this.traverseUpdate) {
            this.traverseUpdate.initialize(this.component, this.userId);
            this.traverseUpdate.register();
        }
    }

    public listenToEvents(): void {

        if (this.traverseUpdate) {
            this.traverseUpdate.listen();
        }
    }
}
