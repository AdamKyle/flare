import React from 'react';

import ServerMessagesProps from './definitions/server-messages-props';
import ServerMessagesDefinition from '../../../../api-definitions/chat/server-messages-definition';
import { useOpenItemDetails } from '../../hooks/use-open-item-details';
import { formatMessageTimestamp } from '../../utils/format-message-timestamp';

import { useGameData } from 'game-data/hooks/use-game-data';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Card from 'ui/cards/card';

const buildServerMessageKey = (
  serverMessage: ServerMessagesDefinition
): string =>
  `${serverMessage.timeStamp}-${serverMessage.id ?? 'none'}-${serverMessage.message}`;

const ServerMessages = ({ server_messages }: ServerMessagesProps) => {
  const { openServerMessageItem } = useOpenItemDetails();
  const { characterId } = useGameData();

  const renderTimestamp = (serverMessage: ServerMessagesDefinition) => {
    return (
      <time
        dateTime={serverMessage.timeStamp}
        className="mr-2 text-xs text-gray-400 dark:text-gray-500"
      >
        {formatMessageTimestamp(serverMessage.timeStamp)}
      </time>
    );
  };

  const renderMessageContent = (serverMessage: ServerMessagesDefinition) => {
    const slotId = serverMessage.id;

    if (slotId === null) {
      return serverMessage.message;
    }

    return (
      <LinkButton
        label={serverMessage.message}
        variant={ButtonVariant.SERVER_MESSAGE_LINK}
        on_click={() => openServerMessageItem(characterId, slotId)}
      />
    );
  };

  const renderMessage = (serverMessage: ServerMessagesDefinition) => {
    return (
      <li key={buildServerMessageKey(serverMessage)}>
        {renderTimestamp(serverMessage)}
        <span className="font-bold">{renderMessageContent(serverMessage)}</span>
      </li>
    );
  };

  return (
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 text-pink-200 dark:bg-gray-800 dark:text-pink-400">
          <ul className="space-y-2">{server_messages.map(renderMessage)}</ul>
        </div>
      </Card>
    </div>
  );
};

export default ServerMessages;
