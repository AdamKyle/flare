import { createRoot } from 'react-dom/client';
import React from "react";
import GuideButton from "./guide-button";

type UserIdProps = {userId: number;}

const guideButton = document.getElementById('guide-button');

if (guideButton !== null) {

    const player = document.head.querySelector<HTMLMetaElement>('meta[name="player"]');

    const props: UserIdProps = {
        userId: player === null ? 0 : parseInt(player.content),
    }

    const element = document.querySelector('#guide-button');

    const value = element?.getAttribute('data-open-modal');

    let forceOpenModal = false;

    if (typeof value !== 'undefined') {
        if (value === 'true') {
            forceOpenModal = true;
        }
    }

    const root = createRoot(guideButton);
    root.render(<GuideButton user_id={props.userId} force_open_modal={forceOpenModal} />,);
}
