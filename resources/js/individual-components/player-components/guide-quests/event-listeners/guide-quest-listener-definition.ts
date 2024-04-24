import Listener from "../../../../game/lib/game/event-listeners/listener";
import GuideButton from "../guide-button";
import Game from "../../../../game/game";

export default interface GuideQuestListenerDefinition extends Listener {
    /**
     *
     * @param component
     * @param userId
     */
    initialize: (component: Game | GuideButton, userId: number) => void;
}
