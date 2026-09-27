import { PublicEntityChatCommand } from '../enums/public-entity-chat-command';

export const parsePublicEntityCommand = (
  text: string
): PublicEntityChatCommand | null => {
  const normalizedCommand = text.trim().toLowerCase();

  if (normalizedCommand === PublicEntityChatCommand.PC) {
    return PublicEntityChatCommand.PC;
  }

  if (normalizedCommand === PublicEntityChatCommand.PCT) {
    return PublicEntityChatCommand.PCT;
  }

  return null;
};
