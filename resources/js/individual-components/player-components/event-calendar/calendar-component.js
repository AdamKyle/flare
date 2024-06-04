import { createRoot } from "react-dom/client";
import React from "react";
import Calendar from "./calendar";
var calendarElement = document.getElementById("player-event-calendar");
if (calendarElement !== null) {
    var root = createRoot(calendarElement);
    root.render(React.createElement(Calendar, null));
}
//# sourceMappingURL=calendar-component.js.map
