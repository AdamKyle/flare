import { useEffect, useState } from "react";
import EventSystemDeffintion from "../../../../../../event-system/deffintions/event-system-deffintion";
import { CharacterCardEvents } from "../EventTypes/CharacterCardEvents";

export const useManageCharacterCardVisibility = (
    eventSystem: EventSystemDeffintion,
) => {
    const closeCardEventEmitter = eventSystem.isEventRegistered(
        CharacterCardEvents.CLOSE_EVENT,
    )
        ? eventSystem.getEventEmitter<{ [key: string]: boolean }>(
              CharacterCardEvents.CLOSE_EVENT,
          )
        : eventSystem.registerEvent<{ [key: string]: boolean }>(
              CharacterCardEvents.CLOSE_EVENT,
          );

    const [showCharacterCard, setShowCharacterCard] = useState(false);

    useEffect(() => {
        const closeCardListener = () => setShowCharacterCard(false);

        closeCardEventEmitter.on(
            CharacterCardEvents.CLOSE_EVENT,
            closeCardListener,
        );

        return () => {
            closeCardEventEmitter.off(
                CharacterCardEvents.CLOSE_EVENT,
                closeCardListener,
            );
        };
    }, [closeCardEventEmitter]);

    const openCharacterCard = () => setShowCharacterCard(true);

    return { showCharacterCard, openCharacterCard };
};
