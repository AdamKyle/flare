import React from "react";
import ReactDOM from "react-dom/client";

import { Game } from "./game";

const rootNode = document.getElementById("game-launcher");
const root = ReactDOM.createRoot(rootNode as HTMLElement);

root.render(<Game />);
