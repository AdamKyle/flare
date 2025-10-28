export enum ChatWebsocketEventNames {
  SERVER = 'Game.Messages.Events.ServerMessageEvent',
  EXPLORATION = 'Game.Exploration.Events.ExplorationLogUpdate',
  ANNOUNCEMENT = 'Game.Messages.Events.AnnouncementMessageEvent',
  NPC_MESSAGE = 'Game.Messages.Events.NPCMessageEvent',
  PRIVATE_MESSAGE = 'Game.Messages.Events.PrivateMessageEvent',
  PUBLIC_MESSAGE = 'Game.Messages.Events.MessageSentEvent',
  GLOBAL_MESSAGE = 'Game.Messages.Events.GlobalMessageEvent',
  DELETE_ANNOUNCEMENT = 'Game.Messages.Events.DeleteAnnouncementEvent',
}
