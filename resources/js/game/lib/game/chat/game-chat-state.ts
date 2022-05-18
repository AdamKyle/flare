import ChatType from "./chat-type";
import ServerMessageType from "./server-message-type";
import TabsType from "../types/tabs-type";
import ExplorationMessageType from "./exploration-message-type";

export default interface GameChatState {

    chat: ChatType[] | [];
    server_messages: ServerMessageType[] | [];
    exploration_messages: ExplorationMessageType[] | [];
    message: string;
    is_silenced: boolean;
    can_talk_again_at: string | null;
    tabs: TabsType[],
}
