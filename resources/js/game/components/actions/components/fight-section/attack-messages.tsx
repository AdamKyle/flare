import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import Messages from './partials/messages';
import AttackMessagesProps from './types/attack-messages-props';

const AttackMessages = ({ messages }: AttackMessagesProps): ReactNode => {
  if (isEmpty(messages)) {
    return null;
  }

  return (
    <div className="mt-4 rounded-lg border border-solid border-gray-200 bg-gray-100 p-4 text-sm dark:border-gray-800 dark:bg-gray-700">
      <div className="mx-auto mt-4 flex w-full items-center justify-center gap-x-3 text-lg leading-none">
        <div className="w-full space-y-2 text-center italic">
          <Messages messages={messages} />
        </div>
      </div>
    </div>
  );
};

export default AttackMessages;
