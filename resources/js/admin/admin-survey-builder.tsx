import { createRoot } from "react-dom/client";
import React from "react";
import SurveyBuilder from "./survey-builder/survey-builder";

type AdminChatProps = { user_id: number; survey_id?: number };

const adminSurveyBuilder = document.getElementById("survey-builder");

if (adminSurveyBuilder !== null) {
    const player = document.head.querySelector<HTMLMetaElement>(
        'meta[name="player"]',
    );

    const props: AdminChatProps = {
        user_id: player === null ? 0 : parseInt(player.content),
        survey_id: adminSurveyBuilder.dataset.surveyId
            ? parseInt(adminSurveyBuilder.dataset.surveyId)
            : undefined, // Convert surveyId to number if it exists
    };

    const root = createRoot(adminSurveyBuilder);

    root.render(
        <SurveyBuilder user_id={props.user_id} survey_id={props.survey_id} />,
    );
}
