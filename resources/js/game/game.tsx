import React, { ReactNode } from "react";

import { GameCard } from "./components/game-card";
import { SeerviceContainer } from "../service-container-provider/service-container";

export const Game = (): ReactNode => {
    return (
        <SeerviceContainer>
            <GameCard />
        </SeerviceContainer>
    );
};
