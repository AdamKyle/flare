import { createRoot, Root } from "react-dom/client";
import React from "react";
import FactionLoyaltyTops from "./faction-loyalty-tops";

const factionLoyaltyTopsElement: HTMLElement | null = document.getElementById(
    "faction-loyalty-tops",
);

if (factionLoyaltyTopsElement !== null) {
    const root: Root = createRoot(factionLoyaltyTopsElement);

    const props = {
        defaultPeriod:
            factionLoyaltyTopsElement.dataset.defaultPeriod ?? "current_month",
    };

    root.render(<FactionLoyaltyTops default_period={props.defaultPeriod} />);
}
