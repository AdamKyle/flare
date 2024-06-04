import { createRoot } from "react-dom/client";
import React from "react";
import Shop from "./shop";
var shopElement = document.getElementById("player-shop");
if (shopElement !== null) {
    var root = createRoot(shopElement);
    var player = document.head.querySelector('meta[name="player"]');
    var character = document.head.querySelector('meta[name="character"]');
    var props = {
        userId: player === null ? 0 : parseInt(player.content),
        characterId: character === null ? 0 : parseInt(character.content),
    };
    root.render(
        React.createElement(Shop, {
            character_id: props.characterId,
            user_id: props.userId,
        }),
    );
}
//# sourceMappingURL=shop-component.js.map
