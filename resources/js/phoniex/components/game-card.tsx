import React from "react";
import Tabs from "../ui/tabs/tabs";
import CharacterSheet from "./character-sheet/character-sheet";
import Actions from "./actions/actions";

export default class GameCard extends React.Component {
    render() {
        return (
            <Tabs
                tabs={["Game", "Character Sheet"]}
                icons={["fas fa-dice-d20", "ra ra-player"]}
            >
                <Actions />
                <CharacterSheet />
            </Tabs>
        );
    }
}
