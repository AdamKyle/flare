import { createRoot } from "react-dom/client";
import React from "react";
import EventSchedule from "./event-calendar/event-schedule";
var eventCalendar = document.getElementById("event-calendar");
if (eventCalendar !== null) {
    var root = createRoot(eventCalendar);
    root.render(React.createElement(EventSchedule, null));
}
//# sourceMappingURL=calendar.js.map
