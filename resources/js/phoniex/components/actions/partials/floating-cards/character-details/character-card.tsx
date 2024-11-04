import React, { ReactNode } from "react";
import FloatingCard from "../../../components/icon-section/floating-card";
import CharacterCardDetails from "./character-card-details";
import { serviceContainer } from "../../../../../service-container/core-container";
import EventSystemDeffintion from "../../../../../event-system/deffintions/event-system-deffintion";
import { CharacterCardEvents } from "./EventTypes/CharacterCardEvents";

const CharacterCard = (): ReactNode => {
    const eventSystem =
        serviceContainer().fetch<EventSystemDeffintion>("EventSystem");

    const handleCloseCard = () => {
        const event = eventSystem.getEventEmitter<{ [key: string]: boolean }>(
            CharacterCardEvents.CLOSE_EVENT,
        );

        event.emit(CharacterCardEvents.CLOSE_EVENT, true);
    };

    return (
        <FloatingCard
            title="Character Name (Lvl: 5,000)"
            close_action={handleCloseCard}
        >
            <CharacterCardDetails />
        </FloatingCard>
    );
};

export default CharacterCard;
