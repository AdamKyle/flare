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
}
