import {createRoot, Root} from 'react-dom/client';
import React from "react";
import GameProps from "./lib/game/types/game-props";
import Game from "./game";

const game: HTMLElement | null = document.getElementById('game');

if (game !== null) {

    const player: HTMLMetaElement | null = document.head.querySelector<HTMLMetaElement>('meta[name="player"]');
    const character:HTMLMetaElement | null = document.head.querySelector<HTMLMetaElement>('meta[name="character"]');

    const props: GameProps = {
        userId: player === null ? 0 : parseInt(player.content),
        characterId: character === null ? 0 : parseInt(character.content)
    }

    const root: Root = createRoot(game);
    root.render(<Game characterId={props.characterId} userId={props.userId} />);
}
