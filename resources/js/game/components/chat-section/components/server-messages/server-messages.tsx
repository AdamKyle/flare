import React from 'react';

import ServerMessagesProps from './definitions/server-messages-props';
import { useOpenItemDetails } from '../../hooks/use-open-item-details';

import { useGameData } from 'game-data/hooks/use-game-data';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Card from 'ui/cards/card';

const ServerMessages = ({ server_messages }: ServerMessagesProps) => {
  const { openServerMessageItem } = useOpenItemDetails();
  const { characterId } = useGameData();

  const renderMessages = () => {
    return server_messages.map((server_message) => {
      if (!server_message.id) {
        return (
          <li key={server_message.id}>
            <span className="font-bold">{server_message.message}</span>
          </li>
        );
      }

      return (
        <li key={server_message.id}>
          <span className="font-bold">
            <LinkButton
              label={server_message.message}
              variant={ButtonVariant.SERVER_MESSAGE_LINK}
              on_click={() =>
                openServerMessageItem(
                  characterId,
                  parseInt(server_message.id ?? '0') || 0
                )
              }
            />
          </span>
        </li>
      );
    });
  };

  return (
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 text-pink-200 dark:bg-gray-800 dark:text-pink-400">
          <ul className="space-y-2">{renderMessages()}</ul>
        </div>
      </Card>
    </div>
  );
};

export default ServerMessages;
