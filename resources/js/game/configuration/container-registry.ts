import chatItemComparisonContainer from "../components/modals/item-details/container/chat-item-comparison-container";
import { CoreContainer } from "../lib/containers/core-container";
import kingdomContainer from "../components/kingdoms/container/kingdom-queue-container";
import characterSheetContainer from "../components/character-sheet/container/character-sheet-container";

function containerRegistry(coreContainer: CoreContainer): void {
    // Item Comparison
    chatItemComparisonContainer(coreContainer);

    // Kingdoms
    kingdomContainer(coreContainer);

    // Character Sheet component containers.
    characterSheetContainer(coreContainer);
}

export { containerRegistry };
