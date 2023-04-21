import ChatType from "../../deffinitions/chat-type";

export default interface ChatComponentProps  {
    is_silenced: boolean;

    can_talk_again_at: string | null;

    chat: ChatType[] | [];

    set_tab_to_updated: (key: string) => void;

    push_silenced_message: () => void;

    push_private_message_sent: (messageData: string[]) => void;

    push_error_message: (message: string) => void;
}
