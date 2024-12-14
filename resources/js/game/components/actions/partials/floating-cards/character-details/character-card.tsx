import React, { ReactNode } from "react";

import CharacterCardDetails from "./character-card-details";
import EventSystemDefinition from "../../../../../../event-system/deffintions/event-system-definition";
import { serviceContainer } from "../../../../../../service-container/core-container";
import FloatingCard from "../../../components/icon-section/floating-card";
import { ActionCardEvents } from "../EventTypes/action-cards";

const CharacterCard = (): ReactNode => {
    const eventSystem =
        serviceContainer().fetch<EventSystemDefinition>("EventSystem");

    const handleCloseCard = () => {
        const event = eventSystem.getEventEmitter<{ [key: string]: boolean }>(
            ActionCardEvents.CLOSE_CHARACTER_CARD,
        );

        event.emit(ActionCardEvents.CLOSE_CHARACTER_CARD, true);
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
