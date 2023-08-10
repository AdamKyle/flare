import ChatType from "./deffinitions/chat-type";
import ServerMessageType from "./deffinitions/server-message-type";
import TabsType from "../../../lib/game/types/tabs-type";
import ExplorationMessageType from "./deffinitions/exploration-message-type";

export default interface GameChatState {

    chat: ChatType[] | [];
    announcements: AnnouncementType[]|[],
    server_messages: ServerMessageType[] | [];
    exploration_messages: ExplorationMessageType[] | [];
    message: string;
    is_silenced: boolean;
    can_talk_again_at: string | null;
    tabs: TabsType[];
    selected_chat: string;
    updated_tabs: string[]|[];
}

export interface AnnouncementType {
    id: number;
    message: string;
    expires_at: string;
    expires_at_formatted: string;
}
