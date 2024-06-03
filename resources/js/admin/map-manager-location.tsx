import { createRoot } from "react-dom/client";
import React from "react";
import MapManager from "./map-location-manager/map-manager";

const mapManager = document.getElementById("map-manager");

if (mapManager !== null) {
    const mapId = mapManager.getAttribute("data-map-id");

    const root = createRoot(mapManager);

    root.render(<MapManager mapId={mapId ? parseInt(mapId) : 0} />);
}
