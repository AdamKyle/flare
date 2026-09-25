import clsx from 'clsx';
import React from 'react';

import ExplorationMessageProps from './definitions/exploration-message-props';
import { formatMessageTimestamp } from '../../utils/format-message-timestamp';

import Card from 'ui/cards/card';

const ExplorationMessages = ({
  exploration_messages,
}: ExplorationMessageProps) => {
  return (
    <div className="mx-auto my-4 w-full lg:w-3/4">
      <Card>
        <div className="h-96 w-full overflow-y-auto rounded-md bg-gray-700 p-2 dark:bg-gray-800">
          <ul className="space-y-2">
            {exploration_messages.map((row) => (
              <li
                key={row.id}
                className={clsx(
                  row.isReward
                    ? 'text-glacier-200 dark:text-glacier-400 font-semibold'
                    : 'text-de-york-200 dark:text-de-york-400',
                  { italic: row.makeItalic }
                )}
              >
                <time
                  dateTime={row.timeStamp}
                  className="mr-2 text-xs text-gray-400 dark:text-gray-500"
                >
                  {formatMessageTimestamp(row.timeStamp)}
                </time>
                {row.isReward && <span className="sr-only">Reward: </span>}
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
