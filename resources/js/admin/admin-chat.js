import { createRoot } from "react-dom/client";
import React from "react";
import GameChat from "../game/sections/chat/game-chat";
var adminChat = document.getElementById("administrator-chat");
if (adminChat !== null) {
    var player = document.head.querySelector('meta[name="player"]');
    var props = {
        userId: player === null ? 0 : parseInt(player.content),
    };
    var root = createRoot(adminChat);
    root.render(
        React.createElement(GameChat, {
            user_id: props.userId,
            character_id: 0,
            is_silenced: false,
            can_talk_again_at: null,
            is_admin: true,
            view_port: 1600,
            is_automation_running: false,
        }),
    );
}
//# sourceMappingURL=admin-chat.js.map
