export enum ChatWebSocketChannels {
  SERVER = 'server-message-{userId}',
  AUTOMATION_LOG = 'automation-log-update-{userId}',
  ANNOUNCEMENTS = 'announcement-message',
  NPC_MESSAGE = 'npc-message-{userId}',
  CHAT = 'chat',
  PRIVATE_MESSAGE = 'private-message-{userId}',
}
