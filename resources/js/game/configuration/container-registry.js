import characterSheetContainer from "../components/character-sheet/container/character-sheet-container";
import kingdomContainer from "../components/kingdoms/container/kingdom-queue-container";
import chatItemComparisonContainer from "../components/modals/item-details/container/chat-item-comparison-container";
import timerEventContainer from "../components/timers/container/timer-event-container";
import factionLoyaltyContainer from "../components/faction-loyalty/container/faction-loyalty-container";
function containerRegistry(coreContainer) {
    chatItemComparisonContainer(coreContainer);
    kingdomContainer(coreContainer);
    timerEventContainer(coreContainer);
    characterSheetContainer(coreContainer);
    factionLoyaltyContainer(coreContainer);
}
export { containerRegistry };
//# sourceMappingURL=container-registry.js.map
