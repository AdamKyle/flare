import { createRoot } from "react-dom/client";
import React from "react";
import MapManager from "./map-location-manager/map-manager";
var mapManager = document.getElementById("map-manager");
if (mapManager !== null) {
    var mapId = mapManager.getAttribute("data-map-id");
    var root = createRoot(mapManager);
    root.render(
        React.createElement(MapManager, { mapId: mapId ? parseInt(mapId) : 0 }),
    );
}
//# sourceMappingURL=map-manager-location.js.map
