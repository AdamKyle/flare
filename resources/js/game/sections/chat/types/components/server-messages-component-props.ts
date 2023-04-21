import ServerMessageType from "../../deffinitions/server-message-type";

export default interface ServerMessagesComponentProps {

    server_messages: ServerMessageType[] | [];

    character_id: number;

    view_port: number;

    is_automation_running: boolean;
}
