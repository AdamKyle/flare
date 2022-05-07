import ChatType from "./chat-type";
import ServerMessageType from "./server-message-type";
import TabsType from "../types/tabs-type";

export default interface GameChatState {

    chat: ChatType[] | [];
    server_messages: ServerMessageType[] | [];
    message: string;
    is_silenced: boolean;
    can_talk_again_at: string | null;
    tabs: TabsType[],
}
