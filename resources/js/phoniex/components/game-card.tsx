import React, { ReactNode } from "react";
import Tabs from "../ui/tabs/tabs";
import CharacterSheet from "./character-sheet/character-sheet";
import Actions from "./actions/actions";

export const GameCard = (): ReactNode => {
    return (
        <Tabs
            tabs={["Game", "Character Sheet"]}
            icons={["fas fa-dice-d20", "ra ra-player"]}
        >
            <Actions />
            <CharacterSheet />
        </Tabs>
    );
};
