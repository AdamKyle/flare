import { createRoot } from "react-dom/client";
import React from "react";
import Items from "./items";
var itemsTableComponent = document.getElementById("items-table");
if (itemsTableComponent !== null) {
    var dataAttribute = itemsTableComponent.getAttribute(
        "data-item-table-type",
    );
    var root = createRoot(itemsTableComponent);
    if (dataAttribute === "null") {
        dataAttribute = null;
    }
    root.render(React.createElement(Items, { type: dataAttribute }));
}
//# sourceMappingURL=items-component.js.map
