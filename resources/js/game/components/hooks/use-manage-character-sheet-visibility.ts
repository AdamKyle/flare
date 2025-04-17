import { useEventSystem } from "event-system/hooks/use-event-system";

import UseCharacterSheetVisibilityDefinition from "./definitions/use-character-sheet-visibility-definition";
import { ActionCardEvents } from "../actions/partials/floating-cards/event-types/action-cards";
import { CharacterSheet } from "../character-sheet/event-types/character-sheet";

export const useManageCharacterSheetVisibility =
    (): UseCharacterSheetVisibilityDefinition => {
        const eventSystem = useEventSystem();

        const manageCharacterSheetEmitter =
            eventSystem.fetchOrCreateEventEmitter<{
                [key: string]: boolean;
            }>(CharacterSheet.OPEN_CHARACTER_SHEET);

        const openCharacterSheet = () => {
            const closeCraftingCardEvent = eventSystem.getEventEmitter<{
                [key: string]: boolean;
            }>(ActionCardEvents.OPEN_CRATING_CARD);
            const closeChatCardEvent = eventSystem.getEventEmitter<{
                [key: string]: boolean;
            }>(ActionCardEvents.OPEN_CHAT_CARD);
            const closeCharacterCardEvent = eventSystem.getEventEmitter<{
                [key: string]: boolean;
            }>(ActionCardEvents.OPEN_CHARACTER_CARD);

            closeCraftingCardEvent.emit(
                ActionCardEvents.OPEN_CRATING_CARD,
                false,
            );
            closeChatCardEvent.emit(ActionCardEvents.OPEN_CHAT_CARD, false);
            closeCharacterCardEvent.emit(
                ActionCardEvents.OPEN_CHARACTER_CARD,
                false,
            );

            manageCharacterSheetEmitter.emit(
                CharacterSheet.OPEN_CHARACTER_SHEET,
                true,
            );
        };

        const closeCharacterSheet = () => {
            manageCharacterSheetEmitter.emit(
                CharacterSheet.OPEN_CHARACTER_SHEET,
                false,
            );
        };

        return {
            openCharacterSheet,
            closeCharacterSheet,
        };
    };
