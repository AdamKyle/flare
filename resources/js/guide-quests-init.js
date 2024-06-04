import { createRoot } from "react-dom/client";
import React from "react";
import "reflect-metadata";
import GuideButton from "./individual-components/player-components/guide-quests/guide-button";
var guideButton = document.getElementById("guide-button");
if (guideButton !== null) {
    var player = document.head.querySelector('meta[name="player"]');
    var props = {
        userId: player === null ? 0 : parseInt(player.content),
    };
    var element = document.querySelector("#guide-button");
    var value =
        element === null || element === void 0
            ? void 0
            : element.getAttribute("data-open-modal");
    var forceOpenModal = false;
    if (typeof value !== "undefined") {
        if (value === "true") {
            forceOpenModal = true;
        }
    }
    var root = createRoot(guideButton);
    root.render(
        React.createElement(GuideButton, {
            user_id: props.userId,
            force_open_modal: forceOpenModal,
        }),
    );
}
//# sourceMappingURL=guide-quests-init.js.map
