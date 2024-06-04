import { createRoot } from "react-dom/client";
import React from "react";
import Game from "./game";
var game = document.getElementById("game");
if (game !== null) {
    var player = document.head.querySelector('meta[name="player"]');
    var character = document.head.querySelector('meta[name="character"]');
    var props = {
        userId: player === null ? 0 : parseInt(player.content),
        characterId: character === null ? 0 : parseInt(character.content),
    };
    var root = createRoot(game);
    root.render(
        React.createElement(Game, {
            characterId: props.characterId,
            userId: props.userId,
        }),
    );
}
//# sourceMappingURL=game-launcher.js.map
