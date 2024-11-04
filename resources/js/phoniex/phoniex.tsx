import React, { ReactNode } from "react";
import { GameCard } from "./components/game-card";
import { SeerviceContainer } from "./components/service-container-provider/service-container";

export const Phoniex = (): ReactNode => {
    return (
        <SeerviceContainer>
            <GameCard />
        </SeerviceContainer>
    );
};
