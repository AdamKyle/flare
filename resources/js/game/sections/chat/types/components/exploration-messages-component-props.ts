import ServerMessageType from "../deffinitions/server-message-type";
import ExplorationMessageType from "../deffinitions/exploration-message-type";

export default interface ExplorationMessagesComponentProps {
    exploration_messages: ExplorationMessageType[] | [];
}
