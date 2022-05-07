import ServerMessageType from "../server-message-type";

export default interface ServerMessagesComponentProps {

    server_messages: ServerMessageType[] | [];

    character_id: number;
}
