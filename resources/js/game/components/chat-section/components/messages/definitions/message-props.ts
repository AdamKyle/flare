import ChatType from '../../../../../api-definitions/chat/chat-message-definition';

export default interface MessagesProps {
  is_silenced?: boolean | null;
  can_talk_again_at?: string | null;
  chat: ChatType[];
  set_tab_to_updated: (key: string) => void;
  push_silenced_message: () => void;
  push_private_message_sent: (messageData: string[]) => void;
  push_error_message: (message: string) => void;
  on_send: (text: string) => void;
}
