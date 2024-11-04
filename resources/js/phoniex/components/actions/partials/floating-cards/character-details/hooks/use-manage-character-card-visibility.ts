import { useEffect, useState } from "react";
import EventSystemDeffintion from "../../../../../../event-system/deffintions/event-system-deffintion";
import { ActionCardEvents } from "../EventTypes/action-cards";

export const useManageCharacterCardVisibility = (
    eventSystem: EventSystemDeffintion,
) => {
    const closeCardEventEmitter = eventSystem.isEventRegistered(
        ActionCardEvents.CLOSE_CHARACTER_CARD_EVENT,
    )
        ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
              ActionCardEvents.CLOSE_CHARACTER_CARD_EVENT,
          )
        : eventSystem.registerEvent<{ [key: string]: boolean }>(
              ActionCardEvents.CLOSE_CHARACTER_CARD_EVENT,
          );

    const [showCharacterCard, setShowCharacterCard] = useState(false);

    useEffect(() => {
        const closeCardListener = () => setShowCharacterCard(false);

        closeCardEventEmitter.on(
            ActionCardEvents.CLOSE_CHARACTER_CARD_EVENT,
            closeCardListener,
        );

        return () => {
            closeCardEventEmitter.off(
                ActionCardEvents.CLOSE_CHARACTER_CARD_EVENT,
                closeCardListener,
            );
        };
    }, [closeCardEventEmitter]);

    const openCharacterCard = () => setShowCharacterCard(true);

    return { showCharacterCard, openCharacterCard };
};
