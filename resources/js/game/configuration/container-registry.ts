import characterSheetContainer from "../components/character-sheet/container/character-sheet-container";
import kingdomContainer from "../components/kingdoms/container/kingdom-queue-container";
import chatItemComparisonContainer from "../components/modals/item-details/container/chat-item-comparison-container";
import timerEventContainer from "../components/timers/container/timer-event-container";
import { CoreContainer } from "../lib/containers/core-container";
import factionLoyaltyContainer from "../components/faction-loyalty/container/faction-loyalty-container";
import suggestionsContainer from "../components/suggestions/container/suggestions-container";
import surveyContainer from "../components/survey/container/survey-container";

function containerRegistry(coreContainer: CoreContainer): void {
    // Item Comparison
    chatItemComparisonContainer(coreContainer);

    // Kingdoms
    kingdomContainer(coreContainer);

    // Timer Listeners
    timerEventContainer(coreContainer);

    // Character Sheet component containers.
    characterSheetContainer(coreContainer);

    // Faction Loyalty component
    factionLoyaltyContainer(coreContainer);

    // Suggestions and Bugs component
    suggestionsContainer(coreContainer);

    // Survey component
    surveyContainer(coreContainer);
}

export { containerRegistry };
