import { inject, injectable } from "tsyringe";
import { Channel } from "laravel-echo";
import CoreEventListener from "../../../../../game/lib/game/event-listeners/core-event-listener";
import TopsListenerDefinition from "./tops-listener-definition";
import TopsEventPayload from "../types/tops-event-payload";

@injectable()
export default class TopsListener implements TopsListenerDefinition {
    private channelName?: string;
    private eventName?: string;
    private callback?: (event: TopsEventPayload) => void;
    private channel?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(
        channelName: string,
        eventName: string,
        callback: (event: TopsEventPayload) => void,
    ): void {
        this.channelName = channelName;
        this.eventName = eventName;
        this.callback = callback;
    }

    register(): void {
        this.coreEventListener.initialize();

        if (!this.channelName) {
            return;
        }

        const echo = this.coreEventListener.getEcho();
        this.channel = echo.private(this.channelName);
    }

    listen(): void {
        if (!this.channel || !this.eventName || !this.callback) {
            return;
        }

        this.channel.listen(this.eventName, this.callback);
    }
}
