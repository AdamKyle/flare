import React, { ReactNode } from "react";
import FloatingCard from "../../../components/icon-section/floating-card";
import CharacterCardDetails from "./character-card-details";

const CharacterCard = (): ReactNode => {
    return (
        <FloatingCard
            title="Character Name (Lvl: 5,000)"
            close_action={() => {}}
        >
            <CharacterCardDetails />
        </FloatingCard>
    );
};

export default CharacterCard;
