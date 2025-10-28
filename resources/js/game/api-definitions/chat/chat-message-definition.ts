export type ChatMessageType =
  | 'chat'
  | 'creator-message'
  | 'global-message'
  | 'error-message'
  | 'private-message-sent';

export default interface ChatType {
  color: string;
  map_name: string;
  character_name: string;
  message: string;
  x: number;
  y: number;
  type: ChatMessageType;
  hide_location: boolean;
  user_id: number;
  custom_class: string | null;
  is_chat_bold: boolean;
  is_chat_italic: boolean;
  name_tag: string | null;
}
