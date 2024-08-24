import Listener from "../../../lib/game/event-listeners/listener";
import SurveyComponent from "../survey-component";

export default interface SurveyEventDefinition extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: SurveyComponent, userId: number) => void;
}
