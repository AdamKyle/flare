import { createRoot, Root } from "react-dom/client";
import React from "react";
import ExplorationTops from "./exploration-tops";

const explorationTopsElement: HTMLElement | null =
    document.getElementById("exploration-tops");

if (explorationTopsElement !== null) {
    const root: Root = createRoot(explorationTopsElement);

    const props = {
        defaultPeriod:
            explorationTopsElement.dataset.defaultPeriod ?? "current_month",
    };

    root.render(<ExplorationTops default_period={props.defaultPeriod} />);
}
