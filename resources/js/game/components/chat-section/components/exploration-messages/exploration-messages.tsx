import React from 'react';

import ExplorationMessageProps from './definitions/exploration-message-props';

import Card from 'ui/cards/card';

const ExplorationMessages = ({
  exploration_messages,
}: ExplorationMessageProps) => {
  return (
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 text-gray-400 dark:bg-gray-800">
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
