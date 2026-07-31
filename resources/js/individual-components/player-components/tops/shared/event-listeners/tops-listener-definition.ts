import Listener from "../../../../../game/lib/game/event-listeners/listener";
import TopsEventPayload from "../types/tops-event-payload";

export default interface TopsListenerDefinition extends Listener {
    initialize: (
        channelName: string,
        eventName: string,
        callback: (event: TopsEventPayload) => void,
    ) => void;
}
