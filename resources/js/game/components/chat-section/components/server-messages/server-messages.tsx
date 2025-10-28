import React, { useCallback } from 'react';

import ServerMessagesProps from './definitions/server-messages-props';

import Card from 'ui/cards/card';

const ServerMessages = ({ server_messages }: ServerMessagesProps) => {
  const handleItemClick = useCallback(() => {
    console.log('Item clicked');
  }, []);

  return (
    <div className="w-full lg:w-3/4 mx-auto my-4">
      <Card>
        <div className="bg-gray-700 dark:bg-gray-800 p-2 w-full h-96 overflow-y-auto rounded-md text-gray-400">
          <ul className="space-y-4">
            {server_messages.map((row) => (
              <li key={row.id}>
                <span
                  className="underline font-bold cursor-pointer"
                  onClick={handleItemClick}
                >
                  {row.message}
                </span>
              </li>
            ))}
          </ul>
        </div>
      </Card>
    </div>
  );
};

export default ServerMessages;
