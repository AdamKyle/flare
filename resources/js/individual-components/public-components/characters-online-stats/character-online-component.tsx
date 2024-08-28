import { createRoot, Root } from "react-dom/client";
import React from "react";
import CharactersOnlineContainer from "./characters-online-container";

const charactersOnline: HTMLElement | null =
    document.getElementById("characters-online");

if (charactersOnline !== null) {
    const root: Root = createRoot(charactersOnline);

    root.render(<CharactersOnlineContainer />);
}
