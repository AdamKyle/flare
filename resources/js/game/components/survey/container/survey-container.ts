import { CoreContainer } from "../../../lib/containers/core-container";
import SurveyAjax from "../ajax/survey-ajax";
import surveyEventsContainer from "./survey-events-container";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function surveyContainer(container: CoreContainer) {
    // Register events:
    surveyEventsContainer(container);

    container.register("survey-ajax", {
        useClass: SurveyAjax,
    });
}

export default surveyContainer;
