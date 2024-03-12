import Listener from "../../../../game/lib/game/event-listeners/listener";
import GuideButton from "../guide-button";

export default interface GuideQuestListenerDefinition extends Listener {

    /**
     *
     * @param component
     * @param userId
     */
    initialize: (component: GuideButton, userId: number) => void;
}
