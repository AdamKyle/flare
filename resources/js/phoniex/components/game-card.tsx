import React from "react";
import Tabs from "../ui/tabs/tabs";
import CharacterSheet from "./character-sheet/character-sheet";
import Actions from "./actions/actions";
import UIComponents from "./ui-components/ui-components";

export default class GameCard extends React.Component {
    render() {
        return (
            <Tabs
                tabs={["Game", "Character Sheet", "UI Components"]}
                icons={["fas fa-dice-d20", "ra ra-player", "far fa-file-alt"]}
            >
                <Actions />
                <CharacterSheet />
                <UIComponents />
            </Tabs>
        );
    }
}
