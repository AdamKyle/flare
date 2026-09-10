export const EventMessageTypes = {
  CREATOR_MESSAGE: 'creator-message',
  NPC_MESSAGE: 'npc-message',
  PRIVATE_MESSAGE_RECEIVED: 'private-message-received',
  GLOBAL_MESSAGE: 'global-message',
} as const;

export type EventMessageType =
  (typeof EventMessageTypes)[keyof typeof EventMessageTypes];
