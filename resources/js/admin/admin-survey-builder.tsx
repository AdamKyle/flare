import { createRoot } from "react-dom/client";
import React from "react";
import SurveyBuilder from "./survey-builder/survey-builder";

type AdminChatProps = { userId: number };

const adminSurveyBuilder = document.getElementById("survey-builder");

if (adminSurveyBuilder !== null) {
    const player = document.head.querySelector<HTMLMetaElement>(
        'meta[name="player"]',
    );

    const props: AdminChatProps = {
        userId: player === null ? 0 : parseInt(player.content),
    };

    const root = createRoot(adminSurveyBuilder);

    root.render(<SurveyBuilder user_id={props.userId} />);
}
