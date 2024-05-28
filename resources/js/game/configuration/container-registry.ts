import characterSheetContainer from "../components/character-sheet/container/character-sheet-container";
import kingdomContainer from "../components/kingdoms/container/kingdom-queue-container";
import chatItemComparisonContainer from "../components/modals/item-details/container/chat-item-comparison-container";
import timerEventContainer from "../components/timers/container/timer-event-container";
import { CoreContainer } from "../lib/containers/core-container";

function containerRegistry(coreContainer: CoreContainer): void {
    // Item Comparison
    chatItemComparisonContainer(coreContainer);

    // Kingdoms
    kingdomContainer(coreContainer);

    // Timer Listeners
    timerEventContainer(coreContainer);

    // Character Sheet component containers.
    characterSheetContainer(coreContainer);
}

export { containerRegistry };
