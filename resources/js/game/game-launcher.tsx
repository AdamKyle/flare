import ReactDOM from "react-dom";
import React from "react";
import Game from './game';
import GameProps from "./lib/game/types/game-props";

const game = document.getElementById('game');

if (game !== null) {

    const player = document.head.querySelector<HTMLMetaElement>('meta[name="player"]');
    const character = document.head.querySelector<HTMLMetaElement>('meta[name="character"]');

    const props: GameProps = {
        userId: player === null ? 0 : parseInt(player.content),
        characterId: character === null ? 0 : parseInt(character.content)
    }

    ReactDOM.render(
        <Game characterId={props.characterId} userId={props.userId} />,
        game
    );
}
