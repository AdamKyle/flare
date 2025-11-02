import React from 'react';

import ServerMessagesProps from './definitions/server-messages-props';

import Card from 'ui/cards/card';

const ServerMessages = ({ server_messages }: ServerMessagesProps) => {
  return (
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 text-pink-200 dark:bg-gray-800 dark:text-pink-400">
          <ul className="space-y-2">
            {server_messages.map((row) => (
              <li key={row.id}>
                <span className="font-bold">{row.message}</span>
              </li>
            ))}
          </ul>
        </div>
      </Card>
    </div>
  );
};

export default ServerMessages;
