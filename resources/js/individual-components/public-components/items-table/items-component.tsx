import { createRoot, Root } from "react-dom/client";
import React from "react";
import Items from "./items";

const itemsTableComponent: HTMLElement | null =
    document.getElementById("items-table");

if (itemsTableComponent !== null) {
    let dataAttribute = itemsTableComponent.getAttribute(
        "data-item-table-type",
    );

    const root: Root = createRoot(itemsTableComponent);

    // Data attributes seem to cast null as a string.
    if (dataAttribute === "null") {
        dataAttribute = null;
    }

    root.render(<Items type={dataAttribute} />);
}
