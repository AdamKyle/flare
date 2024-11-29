import { createRoot, Root } from "react-dom/client";
import React from "react";
import Calendar from "./calendar";

const calendarElement: HTMLElement | null = document.getElementById(
    "player-event-calendar",
);

if (calendarElement !== null) {
    const inGameAttribute = calendarElement.getAttribute("data-in-game");
    const isInGame = inGameAttribute === "true";

    const root: Root = createRoot(calendarElement);

    root.render(<Calendar in_game={isInGame} />);
}
