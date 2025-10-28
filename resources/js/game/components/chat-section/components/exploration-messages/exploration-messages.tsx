import React from 'react';

import ExplorationMessageProps from './definitions/exploration-message-props';

import Card from 'ui/cards/card';

const ExplorationMessages = ({
  exploration_messages,
}: ExplorationMessageProps) => {
  return (
    <div className="w-full lg:w-3/4 mx-auto my-4">
      <Card>
        <div className="bg-gray-700 dark:bg-gray-800 p-2 w-full h-96 overflow-y-auto rounded-md text-gray-400">
          <ul className="space-y-2">
            {exploration_messages.map((row) => (
              <li
                key={row.id}
                className={`${row.make_italic ? 'italic' : ''} ${row.is_reward ? 'text-green-400' : ''}`}
              >
                {row.message}
              </li>
            ))}
          </ul>
        </div>
      </Card>
    </div>
  );
};

export default ExplorationMessages;
