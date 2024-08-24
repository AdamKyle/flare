import { CoreContainer } from "../../../lib/containers/core-container";
import SurveyEvent from "../event-listeners/survey-event";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function surveyEventsContainer(container: CoreContainer) {
    // Register the Capital City Events for when the building upgrade, repair table updates.
    container.register("SurveyEventDefinition", {
        useClass: SurveyEvent,
    });
}

export default surveyEventsContainer;
