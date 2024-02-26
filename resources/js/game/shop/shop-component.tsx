import {createRoot, Root} from 'react-dom/client';
import React from "react";
import Shop from "./shop";

const shopElement: HTMLElement | null = document.getElementById('player-shop');

if (shopElement !== null) {

    const root: Root = createRoot(shopElement);

    const player: HTMLMetaElement | null = document.head.querySelector<HTMLMetaElement>('meta[name="player"]');
    const character:HTMLMetaElement | null = document.head.querySelector<HTMLMetaElement>('meta[name="character"]');

    const props = {
        userId: player === null ? 0 : parseInt(player.content),
        characterId: character === null ? 0 : parseInt(character.content)
    }

    root.render(<Shop character_id={props.characterId} user_id={props.userId} />);
}
