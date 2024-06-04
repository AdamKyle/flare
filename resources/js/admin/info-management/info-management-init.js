import { createRoot } from "react-dom/client";
import React from "react";
import InfoManagement from "./info-management";
var infoManagementInit = document.getElementById("info-management");
if (infoManagementInit !== null) {
    var player = document.head.querySelector('meta[name="player"]');
    var props = {
        userId: player === null ? 0 : parseInt(player.content),
    };
    var element = document.querySelector("#info-management");
    var value =
        element === null || element === void 0
            ? void 0
            : element.getAttribute("data-info-id");
    var infoPageId = 0;
    if (typeof value !== "undefined") {
        if (value !== null) {
            infoPageId = parseInt(value) || 0;
        }
    }
    var root = createRoot(infoManagementInit);
    root.render(
        React.createElement(InfoManagement, {
            user_id: props.userId,
            info_page_id: infoPageId,
        }),
    );
}
//# sourceMappingURL=info-management-init.js.map
