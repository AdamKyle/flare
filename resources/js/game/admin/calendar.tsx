import { createRoot } from 'react-dom/client';
import React from "react";
import EventSchedule from "./event-calendar/event-schedule";

const eventCalendar = document.getElementById('event-calendar');

if (eventCalendar !== null) {

    const root = createRoot(eventCalendar);

    root.render(<EventSchedule />);
}
