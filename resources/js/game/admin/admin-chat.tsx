import ReactDOM from "react-dom";
import React from "react";
import GameChat from "../sections/chat/game-chat";

type AdminChatProps = {userId: number;}

const adminChat = document.getElementById('administrator-chat');

if (adminChat !== null) {

    const player = document.head.querySelector<HTMLMetaElement>('meta[name="player"]');

    const props: AdminChatProps = {
        userId: player === null ? 0 : parseInt(player.content),
    }

    ReactDOM.render(
        <GameChat
            user_id={props.userId}
            character_id={0}
            is_silenced={false}
            can_talk_again_at={null}
            is_admin={true}
            view_port={1600}
        />,
        adminChat
    );
}
