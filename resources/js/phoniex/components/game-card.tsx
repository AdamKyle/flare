import React from "react";
import Fight from "./fighting/fight";
import Tabs from "../ui/tabs/tabs";
import CharacterSheet from "./character-sheet/character-sheet";
import Map from "./map/map";
import GameCardProps from "./types/game-card-props";

export default class GameCard extends React.Component<
    GameCardProps
> {

    render() {
        return (
            <Tabs
                tabs={['Fight', 'Character Sheet', 'Map']}
                icons={['ra ra-sword', 'ra ra-player', 'fas fa-map-signs']}
            >
                <Fight />
                <CharacterSheet />
                <Map />
            </Tabs>
        );

    }
}
