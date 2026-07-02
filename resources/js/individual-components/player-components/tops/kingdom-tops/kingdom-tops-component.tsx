import { createRoot, Root } from "react-dom/client";
import React from "react";
import KingdomTops from "./kingdom-tops";

const kingdomTopsElement: HTMLElement | null =
    document.getElementById("kingdom-tops");

if (kingdomTopsElement !== null) {
    const root: Root = createRoot(kingdomTopsElement);

    const props = {
        defaultPeriod:
            kingdomTopsElement.dataset.defaultPeriod ?? "current_month",
    };

    root.render(<KingdomTops default_period={props.defaultPeriod} />);
}
