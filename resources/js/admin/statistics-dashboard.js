import { createRoot } from "react-dom/client";
import React from "react";
import Dashboard from "./statistics/dashboard";
var administratorStatistics = document.getElementById(
    "administrator-statistics",
);
if (administratorStatistics !== null) {
    var root = createRoot(administratorStatistics);
    root.render(React.createElement(Dashboard, null));
}
//# sourceMappingURL=statistics-dashboard.js.map
