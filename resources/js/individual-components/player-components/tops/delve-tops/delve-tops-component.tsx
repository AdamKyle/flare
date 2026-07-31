import { createRoot, Root } from "react-dom/client";
import React from "react";
import DelveTops from "./delve-tops";

const delveTopsElement: HTMLElement | null =
    document.getElementById("delve-tops");

if (delveTopsElement !== null) {
    const root: Root = createRoot(delveTopsElement);

    const props = {
        defaultPeriod:
            delveTopsElement.dataset.defaultPeriod ?? "current_month",
    };

    root.render(<DelveTops default_period={props.defaultPeriod} />);
}
