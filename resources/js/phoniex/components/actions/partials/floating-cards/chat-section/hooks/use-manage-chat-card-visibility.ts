import { useEffect, useState } from "react";
import EventSystemDeffintion from "../../../../../../event-system/deffintions/event-system-deffintion";
import { ActionCardEvents } from "../../EventTypes/action-cards";
import useManageChatCardVisibilityDefinition from "./deffinitions/use-manage-chat-card-visibility-deffinition";
import UseManageChatCardVisibilityState from "./types/use-manage-chat-card-visibility-state";

export const useManageChatCardVisibility = (
    eventSystem: EventSystemDeffintion,
): useManageChatCardVisibilityDefinition => {
    const closeCardEventEmitter = eventSystem.isEventRegistered(
        ActionCardEvents.CLOSE_CHAT_CARD,
    )
        ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
              ActionCardEvents.CLOSE_CHAT_CARD,
          )
        : eventSystem.registerEvent<{ [key: string]: boolean }>(
              ActionCardEvents.CLOSE_CHAT_CARD,
          );

    const [showChatCard, setShowChatCard] =
        useState<UseManageChatCardVisibilityState["showChatCard"]>(false);

    useEffect(() => {
        const closeCardListener = () => setShowChatCard(false);

        closeCardEventEmitter.on(
            ActionCardEvents.CLOSE_CHAT_CARD,
            closeCardListener,
        );

        return () => {
            closeCardEventEmitter.off(
                ActionCardEvents.CLOSE_CHAT_CARD,
                closeCardListener,
            );
        };
    }, [closeCardEventEmitter]);

    const openChatCard = () => {
        const closeCharacterCardEvent = eventSystem.getEventEmitter<{
            [key: string]: boolean;
        }>(ActionCardEvents.CLOSE_CHARACTER_CARD);
        const closeCraftingCardEvent = eventSystem.getEventEmitter<{
            [key: string]: boolean;
        }>(ActionCardEvents.CLOSE_CRATING_CARD);

        closeCharacterCardEvent.emit(
            ActionCardEvents.CLOSE_CHARACTER_CARD,
            true,
        );
        closeCraftingCardEvent.emit(ActionCardEvents.CLOSE_CRATING_CARD, true);

        setShowChatCard(true);
    };

    return { showChatCard, openChatCard };
};
