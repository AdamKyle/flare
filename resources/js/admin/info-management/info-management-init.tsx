import { createRoot } from "react-dom/client";
import React from "react";
import InfoManagement from "./info-management";

type AdminChatProps = { userId: number };

const infoManagementInit = document.getElementById("info-management");

if (infoManagementInit !== null) {
    const player = document.head.querySelector<HTMLMetaElement>(
        'meta[name="player"]',
    );

    const props: AdminChatProps = {
        userId: player === null ? 0 : parseInt(player.content),
    };

    const element = document.querySelector("#info-management");

    const value = element?.getAttribute("data-info-id");

    let infoPageId = 0;

    if (typeof value !== "undefined") {
        if (value !== null) {
            infoPageId = parseInt(value) || 0;
        }
    }

    const root = createRoot(infoManagementInit);

    root.render(
        <InfoManagement user_id={props.userId} info_page_id={infoPageId} />,
    );
}
