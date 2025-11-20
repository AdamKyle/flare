export enum ChatWebSocketChannels {
  SERVER = 'server-message-{userId}',
  EXPLORATION = 'exploration-log-update-{userId}',
  ANNOUNCEMENTS = 'announcement-message',
  NPC_MESSAGE = 'npc-message-{userId}',
  CHAT = 'chat',
  PRIVATE_MESSAGE = 'private-message-{userId}',
}
