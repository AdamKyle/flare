export interface PublicChatHistoryDefinition {
  message: string;
  x_position: number;
  y_position: number;
  color: string;
  hide_location: boolean;
  user_id: number;
  updated_at: string;
  created_at: string;
  id: number;
  map: string;
  custom_class: string;
  is_chat_bold: boolean;
  is_chat_italic: boolean;
  name: string;
  name_tag: string | null;
}
