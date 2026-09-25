import SendPrivateChatMessageRequest from '../api/hooks/definitions/send-private-chat-message-request';

const PRIVATE_MESSAGE_COMMAND = /^\/m\s+([^:]+):\s*(.+)$/i;

export const parsePrivateMessageCommand = (
  text: string
): SendPrivateChatMessageRequest | null => {
  const privateMatch = text.match(PRIVATE_MESSAGE_COMMAND);

  if (!privateMatch) {
    return null;
  }

  const [, userName, message] = privateMatch;

  return {
    user_name: userName.trim(),
    message,
  };
};
